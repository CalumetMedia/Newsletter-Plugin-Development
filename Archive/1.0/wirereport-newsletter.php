<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @wordpress-plugin
 * Plugin Name:       WireReport.ca Newsletter - Main
 * Description:       Generate HTML emails and connect to Mailchimp API
 * Version:           1.00
 * Author:            Jean-Francois Lavoie
 */

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

function get_first_paragraph( $str ) {
	$str = wpautop( $str );
	$str = substr( $str, 0, strpos( $str, '</p>' ) + 4 );
	$str = strip_tags( $str, '<a><strong><em>' );

	return $str;
}

add_action( 'admin_menu', 'wirereport_newsletter_setup_menu' );
function wirereport_newsletter_setup_menu() {
	add_menu_page( 'Newsletter', 'Newsletter', 'publish_posts', 'wirereport-newsletter', 'wirereport_newsletter_init' );
	//add_submenu_page('wirereport-newsletter', 'Bi-Weekly', 'Bi-Weekly', 'publish_posts', 'wirereport-biweekly-newsletter', 'wirereport_newsletter_biweekly');
	add_submenu_page( 'wirereport-newsletter', 'Breaking News', 'Breaking News', 'publish_posts', 'wirereport-breakingnews-newsletter', 'wirereport_newsletter_breakingnews' );
	add_submenu_page( 'wirereport-newsletter', 'Friday Summary', 'Friday Summary', 'publish_posts', 'wirereport-friday-summary-newsletter', 'wirereport_newsletter_fridaysummary' );
	add_submenu_page( 'wirereport-newsletter', 'Media News', 'Media News', 'publish_posts', 'wirereport-medianews', 'wirereport_newsletter_medianews' );
	add_submenu_page( 'wirereport-newsletter', 'Daily News Update', 'Daily News Update', 'publish_posts', 'wirereport-daily-update', 'wirereport_newsletter_daily' );
	//add_submenu_page('wirereport-newsletter', 'Weekly PDF', 'Weekly PDF', 'publish_posts', 'wirereport-weeklypdf-newsletter', 'wirereport_newsletter_weeklypdf');
	remove_submenu_page( 'wirereport-newsletter', 'wirereport-newsletter' );
}

function wirereport_newsletter_header( $strSubTitle ) {
	//wp_enqueue_script('jquery');
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_register_style( 'jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );
	wp_enqueue_style( 'jquery-ui' );
	$strXHTML = '<h1>Newsletters - ' . $strSubTitle . '</h1>';

	return $strXHTML;
}

function wirereport_newsletter_init() {
	echo wirereport_newsletter_header( 'Main Menu' );
	echo '<a href="/wp-admin/admin.php?page=wirereport-biweekly-newsletter">Bi-Weekly</a><br/>';
	echo '<a href="#">Breaking News</a><br/>';
	echo '<a href="#">Weekly</a><br/>';
}

function wirereport_prepare_content_block( $strContent, $strLabel ) {
	// if no content, skip everything
	if ( $strContent != '' ) {
		$strContentBlock = '<div style="width:800px">';
		$strContentBlock .= '<h3 style="font-size: 2.5em; padding: 0px; margin: 0px 0px 20px 0px; text-transform:uppercase; color:#B40404; border-bottom: 6px solid #E6E6E6; padding-bottom: 8px;">' . $strLabel . '</h3>';
		$strContentBlock .= '<ul style="padding-left:0px;margin-left:0px; list-style:none;">';
		$strContentBlock .= $strContent;
		$strContentBlock .= '</ul>';
		$strContentBlock .= '</div>';
	}

	return $strContentBlock;
}

/**
 * wirereport_tidy_pdf_content
 * removes wordpress caption tags, image tags for the PDF
 */
function wirereport_tidy_pdf_content( $str_content ){
	$str_content = preg_replace ( '/\[(.*?)\]/s','', $str_content ); 	// WP Shortcodes like caption
	$str_content = preg_replace( '~<img\b[^>]+\bsrc\s?=\s?[\'"](.*?)[\'"]~is', '', $str_content ); // images
	return $str_content;
}

function wirereport_newsletter_biweekly() {
	wirereport_newsletter_showui( 'Bi-Weekly' );
}

function wirereport_newsletter_breakingnews() {
	wirereport_newsletter_showui( 'Breaking News' );
}

function wirereport_newsletter_fridaysummary() {
	wirereport_newsletter_showui( 'Friday Summary' );
}

function wirereport_newsletter_medianews() {
	wirereport_newsletter_showui( 'Media News' );
}

function wirereport_newsletter_daily() {
	wirereport_newsletter_showui( 'Daily Updates' );
}

function wirereport_newsletter_toc_output( $str_name, $arr_content ){
	if(count($arr_content) > 0){ 
			$str_return = '<div id="toc-group">';
			$str_return .= '<h1>'. $str_name .'</h1><ul id="pdf-toc">';
			foreach($arr_content as $x){
					$str_return .= '<li>'.$x->post_title.'</li>';  
			}
			$str_return .= '</ul>';
			$str_return .= '<div class="endofsection"></div></div>';

			return $str_return;
	}
}

function wirereport_newsletter_weeklypdf() {
	// set dates for Monday and Friday of current week
	// this function is called by crontab every Friday
	$strStartDate = date( 'Y-m-d', strtotime( 'monday this week' ) );
	$strEndDate   = date( 'Y-m-d', strtotime( 'friday this week' ) );

	//$strStartDate = '2020-05-03';
	//$strEndDate = '2020-05-09';

	// get posts by date range
	$args  = array(
		'posts_per_page' => '-1',
		'date_query'     => array(
			array(
				'after'     => $strStartDate,
				'before'    => $strEndDate,
				'inclusive' => true
			)
		)
	);
	$query = new WP_Query( $args );
	//$postData = $query->get_posts();
	//error_log( print_r($postData, true) );
    $posts = [];

		$strNewsContent = [];
		$strBriefsContent = [];
		$strPeopleContent = [];
		$strCourtContent = [];
		$strRegulatoryContent = [];
		$strBroadcastContent = [];
		$strTelecomContent = [];

	if ( $query->posts ) {
		
		foreach ( $query->posts as $x ) {
			$categories = get_the_category( $x->ID );
			if ( ! empty( $categories ) ) {
                $posts[] = $x;
				switch ( $categories[0]->name ) {
					case 'News':
						$strNewsContent[] = $x;
						break;
					case 'Briefs':
						$strBriefsContent[] = $x;
						break;
					case 'People':
						$strPeopleContent[] = $x;
						break;
					case 'Court':
						$strCourtContent[] = $x;
						break;
					case 'Regulatory':
						$strRegulatoryContent[] = $x;
						break;
					case 'Broadcast':
						$strBroadcastContent[] = $x;
						break;
					case 'Telecom':
						$strTelecomContent[] = $x;
						break;
				}
			}
		}
	}

	// error_log( print_r($strPeopleContent, true)  );
	/*ob_start();
	$strNewsContent = $strNewsContent;
	$strBriefsContent = $strBriefsContent;
	include(plugin_dir_path(__FILE__).'/pdf/newsletter-pdf-file-cover-toc.php');
	$strHeaderPDF = ob_get_clean();

	ob_start();
	include(plugin_dir_path(__FILE__).'/pdf/newsletter-footer.php');
	$strFooterPDF = ob_get_clean();*/

	ob_start();
	include( plugin_dir_path( __FILE__ ) . '/pdf/newsletter-pdf-file-body.php' );
	$strContentPDF = ob_get_clean();

	include( plugin_dir_path( __FILE__ ) . '/pdf_wkhtmltopdf.php' );
	$strPDFCmd  = 'wkhtmltopdf --print-media-type --page-size A4 -T 0 -B 0 -L 0 -R 0 %s %s %s';
	$strFilePDF = pdf::create( $strContentPDF );

	//rename($strFilePDF, plugin_dir_path(__FILE__).'/pdf/'.date("Y-m-d").'-TheWireReport.pdf');
	//$strPDFFilename = plugin_dir_path(__FILE__).'/pdf/'.date("Y-m-d").'-TheWireReport.pdf';

	rename( $strFilePDF, '/home/wirereport/public_html/wp-content/uploads/secure/' . date( "Y-m-d" ) . '-TheWireReport.pdf' );
	$strPDFFilename = '/home/wirereport/public_html/wp-content/uploads/secure/' . date( "Y-m-d" ) . '-TheWireReport.pdf';

	if ( file_exists( $strPDFFilename ) ) {
		//sendEmail($strPDFFilename, 'WireReport.ca', 'circulation@hilltimes.com', 'circulation@hilltimes.com', 'Hi, here\'s the latest WireReport.ca PDF', date("Y-m-d").' | WireReport.ca PDF', '', 'circulation@hilltimes.com, jf@hilltimes.com, denise.mongeon@bell.ca, mnadeau@hilltimes.com, gilles.villeneuve@parl.gc.ca');
		ob_start();
		$filename = basename( $strPDFFilename );
		$pdfURL   = 'https://thewirereport.ca/wp-content/uploads/secure/' . $filename;

    include( plugin_dir_path( __FILE__ ) . '/templates/friday-email.php' );
		$emailTemplate = ob_get_clean();

		sendEmail( $strPDFFilename, $emailTemplate );

	} else {
		sendEmailError( $strPDFFilename, 'WireReport.ca', 'webmaster@hilltimes.com', 'webmaster@hilltimes.com', "Oops, could not find the PDF.\n(" . $strPDFFilename . ')', date( "Y-m-d" ) . ' | WireReport.ca PDF', 'dlittle@hilltimes.com', 'webmaster@hilltimes.com' );
	}

	$strXHTML = '<h1 style="font-size: 2em; margin:.67em 0; display: block; font-weight: 600; color: #23282d; text-align:left;">Newsletter - Weekly PDF</h1>';
	$strXHTML .= '<div style="float:left; width:46%;">';
	$strXHTML .= 'List of the PDF files';
	$strXHTML .= '</div>';
	$strXHTML .= '<div style="float:left; border:1px solid black; margin-left:3%; width:50%;">';
	$strXHTML .= $strHeaderPDF . $strContentPDF;
	$strXHTML .= '</div>';
	echo $strXHTML;
}

function wirereport_newsletter_showui( $strNewsletterType, $isCron = false ) {
	// flag to prevent sending email if content is empty
	$boolSendEmail = true;

	// prepare default date or set them if received from the form
	if ( $_POST["strStartDate"] != '' ) {
		$_SESSION["strStartDate"] = $_POST["strStartDate"];
	}
	if ( $_POST["strEndDate"] != '' ) {
		$_SESSION["strEndDate"] = $_POST["strEndDate"];
	}
	if ( $_POST["updateStoriesFlag"] == 'true' ) {
		$_SESSION["stories"] = $_POST["stories"];
	}

	if ( $_POST["strIntro"] != '' ) {
		$_SESSION["strIntro"] = $_POST["strIntro"];
	}

	if ( $strNewsletterType != 'Friday Summary' ) {
		unset( $_SESSION["strIntro"] );
	}

	if ( $strNewsletterType == 'Breaking News' ) {
		if ( $_SESSION["strStartDate"] == '' ) {
			$_SESSION["strStartDate"] = date( "Y-m-d" );
		}
		if ( $_SESSION["strEndDate"] == '' ) {
			$_SESSION["strEndDate"] = date( "Y-m-d" );
		}
	} elseif ( $strNewsletterType == 'Daily Updates' ) {
		if ( $_SESSION["strStartDate"] == '' ) {
			$_SESSION["strStartDate"] = date( "Y-m-d" );
		}
		if ( $_SESSION["strEndDate"] == '' ) {
			$_SESSION["strEndDate"] = date( "Y-m-d" );
		}
	} elseif ( $strNewsletterType == 'Media News' ) {
		//$_SESSION["strStartDate"] = date('Y-m-d', strtotime('last Thursday'));
		//$_SESSION["strEndDate"] = date("Y-m-d");
		$_SESSION["strStartDate"] = date( 'Y-m-d', strtotime( 'last Wednesday' ) );
		$_SESSION["strEndDate"]   = date( "Y-m-d", strtotime( "Tuesday" ) );
	} elseif ( $strNewsletterType == 'Friday Summary' ) {
		if ( $_SESSION["strStartDate"] == '' ) {
			$_SESSION["strStartDate"] = date( "Y-m-d", strtotime( 'last Monday' ) );
		}
		if ( $_SESSION["strEndDate"] == '' ) {
			$_SESSION["strEndDate"] = date( "Y-m-d" );
		}
	} elseif ( $strNewsletterType == 'Bi-Weekly' ) {
		if ( date( 'D' ) == 'Tue' ) {
			$_SESSION["strStartDate"] = date( 'Y-m-d', strtotime( 'last Thursday' ) );
			$_SESSION["strEndDate"]   = date( "Y-m-d", strtotime( 'last Monday' ) );
		} elseif ( date( 'D' ) == 'Thu' ) {
			$_SESSION["strStartDate"] = date( 'Y-m-d', strtotime( 'last Tuesday' ) );
			$_SESSION["strEndDate"]   = date( "Y-m-d", strtotime( 'last Wednesday' ) );
		} else {
			$_SESSION["strStartDate"] = date( 'Y-m-d', strtotime( 'last Tuesday' ) );
			$_SESSION["strEndDate"]   = date( "Y-m-d", strtotime( 'last Wednesday' ) );
		}
	} else {
		if ( $_SESSION["strStartDate"] == '' ) {
			$_SESSION["strStartDate"] = ( date( 'D' ) != 'Mon' ) ? date( 'Y-m-d', strtotime( 'last Monday' ) ) : date( 'Y-m-d' );
		}
		if ( $_SESSION["strEndDate"] == '' ) {
			$_SESSION["strEndDate"] = date( "Y-m-d", strtotime( 'next friday' ) );
		}
	}

	// set issue date
	/*
	$arrTuesdayIssue = array(1,5,6,7);
	if(in_array(date("N"),$arrTuesdayIssue))
	 $strIssueDate = date("l, F d, Y", strtotime('next tuesday'));
	else
	 $strIssueDate = date("l, F d, Y", strtotime('next thursday'));
	*/

	$strIssueDate = date( "F d, Y" );

	// get posts by date range
	if ( $strNewsletterType == 'Media News' ) {
		$args = array(
			'posts_per_page' => '-1',
			'tax_query'      => array(
				array(
					'taxonomy' => 'columns',
					'field'    => 'slug',
					'terms'    => 'media'
				)
			),
			'date_query'     => array(
				array(
					'after'     => $_SESSION["strStartDate"],
					'before'    => $_SESSION["strEndDate"],
					'inclusive' => true
				)
			)
		);
	} else {
		$args = array(
			'posts_per_page' => '-1',
			'date_query'     => array(
				array(
					'after'     => $_SESSION["strStartDate"],
					'before'    => $_SESSION["strEndDate"],
					'inclusive' => true
				)
			)
		);
	}

	$query = new WP_Query( $args );
	$number_of_posts = $query->found_posts;
	// title of all selected post for preview text in email inbox
	$previewTitleAll = [];
	// prepare stories list and
	// prepare content sorted into categories to insert in newsletter
	// if no posts found, set ui notification text
    $posts = '';
	if ( $query->posts ) {
		$strStoriesList = NULL;
		foreach ( $query->posts as $x ) {
			// push item into correct category
			$categories = get_the_category( $x->ID );

			// prepare stories list
			if ( $isCron ) {
				$strChecked = 'checked="checked"';
			} elseif ( is_array( $_SESSION["stories"] ) && in_array( $x->ID, $_SESSION["stories"] ) ) {
				$strChecked = 'checked="checked"';
			} else {
				$strChecked = '';
			}
			$strStoriesList .= '<input onchange="jQuery(\'#updateStoriesFlag\').val(\'true\'); jQuery(\'#uiForm\').submit();" type="checkbox" name="stories[]" value="' . $x->ID . '" ' . $strChecked . '/> ' . substr( $x->post_date, 0, 10 ) . ' | ' . $x->post_title . '<br/>';

			// prepare item
			// skip content if category is briefs
			if ( $strChecked ) {
				$strContentItem = '';
				$strImg         = get_the_post_thumbnail( $x->ID, 'full', 'style=width:100px; max-width:100px; height:auto;' );
				if ( $strImg ) {
					$strDivWidth    = '680px';
					$strContentItem .= '<div style="float:left; width:100px; margin-right:20px;">';
					$strContentItem .= $strImg;
					$strContentItem .= '</div>';
				} else {
					$strDivWidth = '800px';
				}

				$strContentItem .= '<div style="float:left; width:' . $strDivWidth . '; min-height: 100px;">';
				$strContentItem .= '<a style="text-decoration:none; color:black; font-size:22px; font-weight:bold; line-height:110%; color:black!important;" href="' . get_permalink( $x->ID ) . '">' . $x->post_title . '</a>';
				$strContentItem .= '<p style="margin-top:0; color:black!important;">' . get_first_paragraph( $x->post_content ) . '</p>';
				$strContentItem .= '</div>';
				$strContentItem .= '<div style="clear:both;"></div>';
				$strContentItem .= '<div style="float:left; width:100%; border-bottom:1px solid #ccc; margin-top:15px; margin-bottom:15px;"></div>';
				$strContentItem .= '<div style="clear:both;"></div>';

				// keep last title in memory in case only one story is selected and we're sending a breaking news email (used in subject)
				$strLastCheckedTitle = $x->post_title;

				// store all title and first title will be only used for preview title
				$previewTitleAll[] = $x->post_title;
			} else {
				$strContentItem = '';
			}
			// push content into the right block depending on category
			if ( ! empty( $categories ) ) {
                $posts .= $strContentItem;
			}
		}
	} else {
		$strStoriesList = 'No story found within the date range.<br/>';
	}

	// get newsletter template and fill it w/ content
	// use a different for now for the media newsletter
	if ( $strNewsletterType == 'Media News' ) {
		$strNewsletter = file_get_contents( plugin_dir_path( __FILE__ ) . '/wirereport-media-email-template.php' );
	} else {
		$strNewsletter = file_get_contents( plugin_dir_path( __FILE__ ) . '/wirereport-email-template.php' );
	}

	if ( isset( $previewTitleAll[0] ) ) {
		$strNewsletter = str_replace( '###PREVIEW_TEXT###', $previewTitleAll[0], $strNewsletter );
	}
	$strNewsletter = str_replace( '###SERVER_NAME###', $_SERVER["SERVER_NAME"], $strNewsletter );
	$strNewsletter = str_replace( '###DATE###', $strIssueDate, $strNewsletter );
	$strNewsletter = str_replace( '###POSTS###', $posts, $strNewsletter );
	$strNewsletter = str_replace( '###INTRO###', $_SESSION["strIntro"], $strNewsletter );

	// show ui
	$strXHTML = wirereport_newsletter_header( $strNewsletterType );
	$strXHTML .= '<script>';
	$strXHTML .= 'jQuery(document).ready(function( $ ) { ';
	$strXHTML .= '$( function() { ';
	$strXHTML .= '$("#strStartDate, #strEndDate").datepicker({ ';
	$strXHTML .= '  dateFormat: "yy-mm-dd", ';
	$strXHTML .= '  onSelect: function() { $("#updateStoriesFlag").val("true"); $("#uiForm").submit(); } ';
	$strXHTML .= '}); ';
	$strXHTML .= '} ); ';

	$strXHTML .= ' $("#selectall").click(function() { ';
	$strXHTML .= ' $(":checkbox").prop("checked", true); ';
	$strXHTML .= ' $("#updateStoriesFlag").val("true"); ';
	$strXHTML .= ' $("#uiForm").submit(); ';
	$strXHTML .= ' }); ';

	$strXHTML .= ' $("#unselectall").click(function() { ';
	$strXHTML .= ' $(":checkbox").prop("checked", false); ';
	$strXHTML .= ' $("#updateStoriesFlag").val("true"); ';
	$strXHTML .= ' $("#uiForm").submit(); ';
	$strXHTML .= ' }); ';

	$strXHTML .= ' $(".btn:not(.updateSubjectLine)").click(function() { ';
	$strXHTML .= ' return confirm("Do you want to continue?"); ';
	$strXHTML .= ' }); ';

	$strXHTML .= '}); ';
	$strXHTML .= '</script>';

	$strXHTML .= '<form id="uiForm" method="post">';

	// show date range
	$strXHTML .= '<div style="float:left; width:46%;">';
	$strXHTML .= '<div style="float:left; width:100%; font-weight:bold; margin-bottom:4px; font-size:16px;">Date range</div>';
	$strXHTML .= '<div style="float:left; width:100%;">';
	$strXHTML .= 'Start Date: <input style="width:90px;" name="strStartDate" id="strStartDate" type="text" value="' . $_SESSION["strStartDate"] . '"/> - End date: <input style="width:90px;" name="strEndDate" id="strEndDate" type="text" value="' . $_SESSION["strEndDate"] . '"/>';
	$strXHTML .= '</div>';

	$subjectLine = stripslashes( get_option( 'ht_newsletter_subject_line_' . $strNewsletterType ) );

	$strXHTML .= '<div style="float:left; width:100%; margin-top:20px; font-weight:bold; margin-bottom:4px; font-size:16px;">Subject Line</div>';
	$strXHTML .= '<div style="float:left; width:100%;">';
	$strXHTML .= '<p class="titlemsg" style="padding: 10px; background-color: #f7f769; display: none; font-weight: bold; float: left; width: 100%;"></p>';
	$strXHTML .= '<input type="text" name="strSubjectLine" data-type="' . $strNewsletterType . '" value="' . $subjectLine . '" style="width: 100%;" />';
	$strXHTML .= '</div>';
	$strXHTML .= '<div style="float:left; width:100%; margin-top:5px;">';
	$strXHTML .= '<button class="btn updateSubjectLine">Update</a>';
	$strXHTML .= '</div>';

	if ( $strNewsletterType == 'Friday Summary' ) {
		$strXHTML .= '<div style="float:left; width:100%; margin-top:20px; font-weight:bold; margin-bottom:4px; font-size:16px;">Introduction text for Friday Summary</div>';
		$strXHTML .= '<div style="float:left; width:100%;">';
		$strXHTML .= '<textarea rows="5" cols="80" name="strIntro">' . $_SESSION["strIntro"] . '</textarea>';
		$strXHTML .= '</div>';
		$strXHTML .= '<div style="float:left; width:100%; margin-top:5px;">';
		$strXHTML .= '<input class="btn" type="submit" name="updateIntro" value="Update"/>';
		$strXHTML .= '</div>';
	}

	// show stories list based on date range
	$strXHTML .= '<style> #selectall, #unselectall { margin-top:-2px; font-weight:normal; font-size:12px; color:#6d99c4; cursor:pointer; } .brack { font-size:12px; font-weight:normal; color:#ccc; } </style>';
	$strXHTML .= '<div style="float:left; width:100%; font-weight:bold; margin-top:20px; margin-bottom:4px; font-size:16px;">Stories <span class="brack">[</span><span id="selectall">select all</span> <span class="brack">|</span> <span id="unselectall">unselect all</span><span class="brack">]</span></div>';
	$strXHTML .= '<div id="chk" style="float:left; width:100%;">';
	$strXHTML .= $strStoriesList;
	$strXHTML .= '</div>';

	$strXHTML .= '<style>
   .btn {
     background-color:black;
     color:white;
     padding:8px;
     border:0px solid black;
     border-radius: 4px;
     cursor:pointer;
   }

   .btn:hover {
     background-color:grey;
   }</style>';

	$strXHTML .= '<div style="float:left; width:100%; margin-top:20px;">';
	$strXHTML .= '<input class="btn" type="submit" name="sendTestEmail" value="Send Email Test"/>&#160;&#160;';
	$strXHTML .= '<input class="btn" type="submit" name="sendEmail" value="Send Email"/>';
	$strXHTML .= '<input type="hidden" id="updateStoriesFlag" name="updateStoriesFlag" value="false"/>';
	$strXHTML .= '</div>';

	if ( ( $posts == '' ) && ( $_POST["sendTestEmail"] || $_POST["sendEmail"] ) ) {
		$strXHTML      .= '<div style="float:left; width:100%; margin-top:20px;">';
		$strXHTML      .= 'The newsletter is empty and was not sent. Make sure you select at least one content piece or tell JF his code is broken.';
		$strXHTML      .= '</div>';
		$boolSendEmail = false;
	} elseif ( $_POST["sendTestEmail"] || $_POST["sendEmail"] ) {
		$strXHTML .= '<div style="float:left; width:100%; margin-top:20px;">';
		$strXHTML .= 'The newsletter was sent!';
		$strXHTML .= '</div>';
	}

	$strXHTML .= '</div>';

	// show newsletter preview
	$strXHTML .= '<div style="float:left; border:1px solid black; margin-left:3%; width:50%;">';
	$strXHTML .= $strNewsletter;
	$strXHTML .= '</div>';

	$strXHTML .= '</form>';

	if ( $_POST["sendTestEmail"] && $boolSendEmail ) {
		generateCampaign( $strIssueDate, $strNewsletter, $strNewsletterType, 'test', $strLastCheckedTitle, $subjectLine );
	} elseif ( ( $_POST["sendEmail"] || ( ( $strNewsletterType == 'Bi-Weekly' || $strNewsletterType == 'Media News' || $strNewsletterType == 'Daily Updates' ) && $isCron ) ) && $boolSendEmail ) {
		if( $number_of_posts > 0 ){
			generateCampaign( $strIssueDate, $strNewsletter, $strNewsletterType, 'send', $strLastCheckedTitle, $subjectLine );
		}
	}

	// can only find interest main id and sub id using the api so this is simply helper code to find those damn IDs
	// not needed for production
	//include_once(get_template_directory().'/inc/MailChimp.php');
	//$MailChimp = new MailChimp('497f5e8ded06847d248de310ca1fbecf-us10');
	//$strCmd = '/lists/762d18fda1/interest-categories/';  // start with this, then drill down with the next line to get to subgroups ids
	//$strCmd = '/lists/762d18fda1/interest-categories/2cc2c3e44f/interests';
	//$arrCampaignContentResult = $MailChimp->get($strCmd, $arrOptions);
	//print_r($arrCampaignContentResult);
	//exit();

	echo $strXHTML;
}

function generateCampaign( $strIssueDate, $strNewsletter, $strNewsletterType, $strMode, $strSubjectAux, $subjectLine = '' ) {
	include_once( get_template_directory() . '/inc/MailChimp.php' );
	$MailChimp = new MailChimp( '402cae1cc2b5a3000f6b18ad18f3b6ff-us10' );

	if ( $strNewsletterType == 'Bi-Weekly' ) {
		// set correct title depending on the day
		if ( date( 'D' ) == 'Tue' ) {
			$strTitle = 'Tuesday Newsletter';
		} else {
			$strTitle = 'Thursday Newsletter';
		}

		$conditions = array();
		//$conditions[] = array('field'=>'interests-2cc2c3e44f', 'op'=>'interestcontains', 'value'=>array('4c0a65619b'));
		$conditions[]                             = array(
			'condition_type' => 'Interests',
			'field'          => 'interests-2cc2c3e44f',
			'op'             => 'interestcontains',
			'value'          => array( '4c0a65619b' )
		);
		$segment_opts                             = array( 'match' => 'all', 'conditions' => $conditions );
		$arrOptions["recipients"]["segment_opts"] = $segment_opts;
		$arrOptions["settings"]["subject_line"]   = $strTitle . " - " . $strIssueDate;
		$arrOptions["settings"]["title"]          = $subjectLine ? $subjectLine : $strTitle . " - " . $strIssueDate;
	} elseif ( $strNewsletterType == 'Breaking News' ) {
		$strTitle   = 'Breaking News';
		$conditions = array();
		//$conditions[] = array('field'=>'interests-2cc2c3e44f', 'op'=>'interestcontains', 'value'=>array('2a7331fd0e'));
		$conditions[]                             = array(
			'condition_type' => 'Interests',
			'field'          => 'interests-2cc2c3e44f',
			'op'             => 'interestcontains',
			'value'          => array( '2a7331fd0e' )
		);
		$segment_opts                             = array( 'match' => 'all', 'conditions' => $conditions );
		$arrOptions["recipients"]["segment_opts"] = $segment_opts;
		$arrOptions["settings"]["subject_line"]   = $subjectLine ? $subjectLine : 'Breaking News | ' . $strSubjectAux;
		$arrOptions["settings"]["title"]          = 'Breaking News | ' . $strSubjectAux;
	} elseif ( $strNewsletterType == 'Daily Updates' ) {
		$strTitle   = 'Daily News Update';
		$conditions = array();
		//$conditions[] = array('field'=>'interests-2cc2c3e44f', 'op'=>'interestcontains', 'value'=>array('2a7331fd0e'));
		$conditions[]                             = array(
			'condition_type' => 'Interests',
			'field'          => 'interests-2cc2c3e44f',
			'op'             => 'interestcontains',
			'value'          => array( '2a7331fd0e' )
		);
		$segment_opts                             = array( 'match' => 'all', 'conditions' => $conditions );
		$arrOptions["recipients"]["segment_opts"] = $segment_opts;
		$arrOptions["settings"]["subject_line"]   = $subjectLine ? $subjectLine : 'Daily News Update';
		$arrOptions["settings"]["title"]          = 'Daily News Update';
	} elseif ( $strNewsletterType == 'Friday Summary' ) {
		$strTitle   = 'The Wire Report weekly';
		$conditions = array();
		//$conditions[] = array('field'=>'interests-2cc2c3e44f', 'op'=>'interestcontains', 'value'=>array('68427810dd'));
		$conditions[]                             = array(
			'condition_type' => 'Interests',
			'field'          => 'interests-2cc2c3e44f',
			'op'             => 'interestcontains',
			'value'          => array( '68427810dd' )
		);
		$segment_opts                             = array( 'match' => 'all', 'conditions' => $conditions );
		$arrOptions["recipients"]["segment_opts"] = $segment_opts;
		$arrOptions["settings"]["subject_line"]   = $subjectLine ? $subjectLine : $strTitle . " - " . $strIssueDate;
		$arrOptions["settings"]["title"]          = $strTitle . " - " . $strIssueDate;
	} elseif ( $strNewsletterType == 'Media News' ) {
		$strTitle   = 'The Wire Report | Media News';
		$conditions = array();
		//$conditions[] = array('field'=>'interests-2cc2c3e44f', 'op'=>'interestcontains', 'value'=>array('60e85f6a36'));
		$conditions[]                             = array(
			'condition_type' => 'Interests',
			'field'          => 'interests-2cc2c3e44f',
			'op'             => 'interestcontains',
			'value'          => array( '60e85f6a36' )
		);
		$segment_opts                             = array( 'match' => 'all', 'conditions' => $conditions );
		$arrOptions["recipients"]["segment_opts"] = $segment_opts;
		$arrOptions["settings"]["subject_line"]   = $subjectLine ? $subjectLine : $strTitle . " - " . $strIssueDate;
		$arrOptions["settings"]["title"]          = $strTitle . " - " . $strIssueDate;
	} else {
		$strTitle                               = '';
		$arrOptions["settings"]["subject_line"] = $subjectLine ? $subjectLine : $strIssueDate;
		$arrOptions["settings"]["title"]        = $strTitle . " - " . $strIssueDate;
	}

	// real wirereport list
	//$arrOptions["recipients"]["list_id"] = "df2bf9c628"; // test list
	// obsolete, now using groups
	//if($strNewsletterType == 'Media News')
	//$arrOptions["recipients"]["list_id"] = "1126f7cbdb";
	//else

	$arrOptions["recipients"]["list_id"] = "762d18fda1";

	// create campaign
	$arrOptions["type"]                  = "regular";
	$arrOptions["settings"]["from_name"] = "The Wire Report";
	$arrOptions["settings"]["reply_to"]  = "circulation@hilltimes.com";
	$arrCampaignResult                   = $MailChimp->post( 'campaigns', $arrOptions );

	// set campaign content
	unset( $arrOptions );
	$strCmd                   = 'campaigns/' . $arrCampaignResult['id'] . '/content';
	$arrOptions["html"]       = $strNewsletter;
	$arrCampaignContentResult = $MailChimp->put( $strCmd, $arrOptions );


	// send test email
	if ( $strMode == 'test' ) {
		unset( $arrOptions );
		$strCmd = 'campaigns/' . $arrCampaignResult['id'] . '/actions/test';
		//   $arrOptions["test_emails"] = array('jf@hilltimes.com', 'joey@hilltimes.com', 'akarad@thewirereport.ca', 'ahathout@thewirereport.ca', 'ian@hilltimes.com', 'maruf@grype.ca', 'atai.rabbi@grype.ca', 'fajlay.rabbi@grype.ca', 'bannya@grype.ca');
		$arrOptions["test_emails"] = array(
			'shakir@grype.ca',
			'atai.rabbi@grype.ca',
			'fajlay.rabbi@grype.ca',
			'kamrul@grype.ca'
		);
		//$arrOptions["test_emails"] = array('ian@hilltimes.com');
		$arrOptions["send_type"]  = 'html';
		$arrCampaignContentResult = $MailChimp->post( $strCmd, $arrOptions );

		// delete campaign
		$strCmd                   = 'campaigns/' . $arrCampaignResult['id'] . '';
		$arrCampaignContentResult = $MailChimp->delete( $strCmd );
	} // schedule campaign
    elseif ( $strMode == 'schedule' ) {
		unset( $arrOptions );
		$strCmd                      = 'campaigns/' . $arrCampaignResult['id'] . '/actions/schedule';
		$arrOptions["schedule_time"] = date( "Y-m-d" ) . 'T09:30:00+00:00';
		$arrCampaignContentResult    = $MailChimp->post( $strCmd, $arrOptions );
	} // send campaign
	else {
		unset( $arrOptions );
		$strCmd                   = 'campaigns/' . $arrCampaignResult['id'] . '/actions/send';
		$arrCampaignContentResult = $MailChimp->post( $strCmd, $arrOptions );
	}
}

/*******  PDF Update  *******/
function sendEmail( $strPDFFilename, $emailTemplate, $subjectLine = '' ) {

	$content  = file_get_contents( $strPDFFilename );
	$content  = chunk_split( base64_encode( $content ) );
	$uid      = md5( uniqid( time() ) );
	$filename = basename( $strPDFFilename );
	$filename = 'https://thewirereport.ca/wp-content/uploads/secure/' . $filename;

	//$email   = 'circulation@hilltimes.com, jf@hilltimes.com, denise.mongeon@bell.ca, mnadeau@hilltimes.com, gilles.villeneuve@parl.gc.ca';

	include_once( get_template_directory() . '/inc/MailChimp.php' );
	$MailChimp = new MailChimp( '402cae1cc2b5a3000f6b18ad18f3b6ff-us10' );

	$strIssueDate = date( "F j, Y" );
	$strTitle     = 'The Wire Report PDF';

	$arrOptions["settings"]["subject_line"] = $subjectLine ? $subjectLine : $strTitle . " - " . $strIssueDate;
	$arrOptions["settings"]["title"]        = $strTitle . " - " . $strIssueDate;
	$arrOptions["recipients"]["list_id"]    = "68d48eed72";  // Active exclusive WR list

	// create campaign
	$arrOptions["type"]                  = "regular";
	$arrOptions["settings"]["from_name"] = "The Wire Report";
	$arrOptions["settings"]["reply_to"]  = "circulation@hilltimes.com";
	$arrCampaignResult                   = $MailChimp->post( 'campaigns', $arrOptions );

	// set campaign content
	unset( $arrOptions );
	$strCmd                   = 'campaigns/' . $arrCampaignResult['id'] . '/content';
	$arrOptions["html"]       = $emailTemplate;
	$arrCampaignContentResult = $MailChimp->put( $strCmd, $arrOptions );

	if ( $strTest == 'yes' ) {
		unset( $arrOptions );
		$strCmd                    = 'campaigns/' . $arrCampaignResult['id'] . '/actions/test';
		$arrOptions["test_emails"] = array(
			'dlittle@hilltimes.com'
		);
		$arrOptions["send_type"]   = 'html';
		$arrCampaignContentResult  = $MailChimp->post( $strCmd, $arrOptions );

		// delete campaign
		$strCmd                   = 'campaigns/' . $arrCampaignResult['id'] . '';
		$arrCampaignContentResult = $MailChimp->delete( $strCmd );

		return '1';

	} else {
		unset( $arrOptions );
		$time                        = getUTC();
		$strCmd                      = 'campaigns/' . $arrCampaignResult['id'] . '/actions/schedule';
		$arrOptions["schedule_time"] = date( "Y-m-d" ) . 'T' . $time . ':00+00:00';
		$arrCampaignContentResult    = $MailChimp->post( $strCmd, $arrOptions );

		return '1';
	}
}

function getUTC() {
	$hour   = date( "H", time() - date( "Z" ) );
	$minute = date( "i", time() - date( "Z" ) );

	if ( ( $minute >= 0 ) && ( $minute < 15 ) ) {
		$min = 15;
	} else if ( ( $minute >= 15 ) && ( $minute < 30 ) ) {
		$min = 30;
	} else if ( ( $minute >= 30 ) && ( $minute < 45 ) ) {
		$min = 45;
	} else if ( ( $minute >= 45 ) && ( $minute < 59 ) ) {
		$hour ++;
		$min = 15;
	}

	return $hour . ':' . $min;
}

function debug_log( $log ) {
	$textdir = "/home/wirereport/public_html/wp-content/plugins/ht-newsletters/debug.txt";
	file_put_contents( $textdir, $log . PHP_EOL, FILE_APPEND | LOCK_EX );
}


/***********  PDF Update  **************/
function sendEmailError( $strPDFFilename, $from_name, $from_mail, $replyto, $message, $subject, $mailto, $emailbcc = '' ) {
	$uid = md5( uniqid( time() ) );

	// header
	$header = "From: " . $from_name . " <" . $from_mail . ">\r\n";
	$header .= "Reply-To: " . $replyto . "\r\n";

	if ( $emailbcc ) {
		$header .= "Bcc: " . $emailbcc . "\r\n";
	}

	$header .= "MIME-Version: 1.0\r\n";
	$header .= "Content-Type: multipart/mixed; boundary=\"" . $uid . "\"\r\n\r\n";

	// message & attachment
	$nmessage = "--" . $uid . "\r\n";
	$nmessage .= "Content-type:text/plain; charset=iso-8859-1\r\n";
	$nmessage .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
	$nmessage .= $message . "\r\n\r\n";

	if ( mail( $mailto, $subject, $nmessage, $header ) ) {
		return true; // Or do something here
	} else {
		return false;
	}
}

function ht_newsletter_admin_footer() {
	?>
    <script>
        (function ($) {
            $(document).ready(function () {
                $('.updateSubjectLine').click(function (e) {
                    e.preventDefault();
                    var $input = $('input[name="strSubjectLine"]');
                    var subjectLine = $input.val();
                    if (!subjectLine) {
                        console.log("Hello");
                    } else {
                        var newsletterType = $input.data('type');
                        var htAjaxUrl = '<?php echo admin_url( 'admin-ajax.php' ) ?>';
                        $.ajax({
                            method: 'POST',
                            url: htAjaxUrl,
                            data: {
                                action: 'ht_save_subject_line',
                                subjectLine: subjectLine,
                                newsletterType: newsletterType
                            }
                        }).done(function (res) {
                            var $titlemsg = $('.titlemsg');

                            $titlemsg.text('Successfully updated the subject line').show().delay(5000).fadeOut();

                            //setTimeout(function() { $titlemsg.hide(); }, 5000);
                        });
                    }
                });
            });
        })(jQuery);
    </script>
	<?php
}

add_action( 'admin_footer', 'ht_newsletter_admin_footer' );

function ht_save_subject_line() {
	$subject_line = isset( $_POST['subjectLine'] ) && $_POST['subjectLine'] ? sanitize_text_field( $_POST['subjectLine'] ) : '';
	if ( ! $subject_line ) {
		wp_send_json_success( [ 'message' => '' ] );
	}
	$newsletter_type = isset( $_POST['newsletterType'] ) && $_POST['newsletterType'] ? $_POST['newsletterType'] : '';
	if ( ! $newsletter_type ) {
		wp_send_json_success( [ 'message' => '' ] );
	}
	$updated = update_option( 'ht_newsletter_subject_line_' . $newsletter_type, $subject_line );
	if ( ! $updated ) {
		wp_send_json_success( [ 'message' => '' ] );
	}
	wp_send_json_success( [ 'message' => 1 ] );
}

add_action( 'wp_ajax_ht_save_subject_line', 'ht_save_subject_line' );