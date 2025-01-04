<html>
 <head>
 <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
      <style>
          div.page-layout-toc { 
              width:205mm;
              height: 285mm;
              overflow: hidden; 
          }

          div.page-layout { 
              width:205mm; 
              overflow: hidden; 
          }

          #pagetopborder{ 
              /* background: url("https://thewirereport.ca/wp-content/plugins/ht-newsletters/pdf/images/top.jpg"); */
              background: url("/home/wirereport/public_html/wp-content/plugins/ht-newsletters/pdf/images/top.jpg");
              background-size: cover; 
              background-repeat: no-repeat;
              width: 100% !important;
              height: 120px;
              float: left;
          }

          #pdfpagecontainer{
              float: left;
              width: 90%;
              padding: 5% 8% 2% 6%;
          }

          @font-face{
              font-family: 'Oswald';
              font-style: normal;
              font-weight: 200;
              src: url('/home/wirereport/public_html/wp-content/plugins/ht-newsletters/pdf/fonts/oswald-v14-latin-200.eot');
              src: url('/home/wirereport/public_html/wp-content/plugins/ht-newsletters/pdf/fonts/oswald-v14-latin-200.eot?#iefix') format('embedded-opentype'),
                  url('/home/wirereport/public_html/wp-content/plugins/ht-newsletters/pdf/fonts/oswald-v14-latin-200.woff') format('woff'),
                  url('/home/wirereport/public_html/wp-content/plugins/ht-newsletters/pdf/fonts/oswald-v14-latin-200.ttf') format('truetype');
          }

          @font-face{
              font-family: 'Oswald';
              font-style: normal;
              font-weight: 400;
              src: url('/home/wirereport/public_html/wp-content/plugins/ht-newsletters/pdf/fonts/oswald-v14-latin-regular.eot');
              src: url('/home/wirereport/public_html/wp-content/plugins/ht-newsletters/pdf/fonts/oswald-v14-latin-regular.eot?#iefix') format('embedded-opentype'),
                  url('/home/wirereport/public_html/wp-content/plugins/ht-newsletters/pdf/fonts/oswald-v14-latin-regular.woff') format('woff'),
                  url('/home/wirereport/public_html/wp-content/plugins/ht-newsletters/pdf/fonts/oswald-v14-latin-regular.ttf') format('truetype');
          }

          #coverdate{
              font-size: 28px;
              font-family: 'Oswald';
              font-weight: 300;
              text-align: center;
              color: #6C6D70;
              font-size: 16pt;
              margin-top: 25px;
              margin-bottom: 40px;
              float: left;
              width: 100%;
          }  

          #toctitle{
              font-family: 'Oswald';
              font-weight: 500;
              text-align: center;
              color: #000;
              font-size: 30pt;
              padding-top: 35px;
              margin-bottom: 15px;
          }  

          #toc-group ul li{
              list-style: none;
              padding: 10px 10px 10px 0px;
              margin-left: 10px;
              font-size: 12pt;
              border-bottom: 1px solid #231f20;
          }
          #toc-group h1{
              width: 100%;
              padding: 7px 12px;
              background: #223033;
              color: #f49545;
              font-size: 22pt;
          }

          #toc-left{
              float: left;
              width: 40%;
              height: 100%;
              margin-left: 4%;
          }

          #toc-left h1{
              float: left;
              width: 100%;
              padding: 7px 12px;
              background: #223033;
              color: #f49545;
              font-size: 22pt;
          }

          #toc-left ul{
              padding: 0;
              margin: 0;
          }

          #toc-left ul li{
              list-style: none;
              padding: 10px 10px 10px 0px;
              margin-left: 10px;
              font-size: 12pt;
              border-bottom: 1px solid #231f20;
          }

          #toc-right{
              float: right;
              width: 40%;
              margin-right: 8%;
              height: 100%;
          }

          #toc-right ul li{
              list-style: none;
              padding: 10px 10px 10px 0px;
              margin-left: 10px;
              font-size: 12pt;
              border-bottom: 1px solid #231f20;
          }

          #toc-right ul{
              padding: 0;
              margin: 0;
          }

          #toc-right h1{
              float: left;
              width: 100%;
              padding: 7px 12px;
              background: #223033;
              color: #ff6b6d;
              font-size: 22pt;
          }          

          #pdf-toc li a{
              font-family: 'Oswald';
              text-decoration: none;
              color: #1B2527;
              font-size: 11pt;
          }

          #pdfcontents{
              width: 100%;
              font-size: 10pt;
              line-height: 1.5em;
          }

          #pdflabel1{
              font-size: 15pt;
              padding: 8px 15px;
              background: #223033;
              color: #E79545;
              margin-bottom: 5px;
          }

          #pdflabel2{
              font-size: 15pt;
              padding: 8px 15px;
              background: #223033;
              color: #FF6B6D;
              margin-bottom: 5px;
          }

          #pdftitle{
            font-size: 20pt;
             color: #223033;
          }

          #pdfbyline{
             font-size: 11pt;
             color: #338D8D;
             margin: 25px 0px;
             font-weight: bold;   
          } 

          #pdfcontents p{
              font-size: 11pt;
              line-height: 1.6em;
              color: #231F20;
              margin-bottom: 20px;
          } 

         .endofsection { 
             page-break-after: always !important;
         }
         
      </style>
 </head>
 <body style="margin:0; padding:0;">
     <div class="page-layout-toc" id="coverpage">
        <div id="pagetopborder"></div>  
        <div id="coverdate"><?php echo date("F j, Y"); ?></div>
        <div id="toctitle">Table of Contents</div>
        
        <?php 

        $strXHTMLTOC = wirereport_newsletter_toc_output( 'News', $strNewsContent );
        $strXHTMLTOC .= wirereport_newsletter_toc_output( 'Briefs', $strBriefsContent );
        $strXHTMLTOC .= wirereport_newsletter_toc_output( 'Court', $strCourtContent );
        $strXHTMLTOC .= wirereport_newsletter_toc_output( 'Regulatory', $strRegulatoryContent );
        $strXHTMLTOC .= wirereport_newsletter_toc_output( 'Broadcast', $strBroadcastContent );
        $strXHTMLTOC .= wirereport_newsletter_toc_output( 'Telecom', $strTelecomContent );
        $strXHTMLTOC .= wirereport_newsletter_toc_output( 'People', $strPeopleContent );
        
        echo $strXHTMLTOC;

        ?>
        <div style="clear:both;"></div>
        <div class="endofsection"></div>
    </div> 


    <?php
    //News
    $strXHTML .= '<div class="page-layout">';
    $strXHTML .= '<div id="pagetopborder"></div>';
    $strXHTML .= '<div style="clear:both;"></div>';

    if(count($strNewsContent) > 0){
          $intWordsCount = 0;
          foreach($strNewsContent as $x){                
                        $contents = preg_replace('/<iframe.*?\/iframe>/i','', $x->post_content);
                        $contents = wpautop( $contents );

                        $strXHTML .= '<div id="pdfpagecontainer">';
                        $strXHTML .= '<span id="pdflabel1">News</span>';
                        $strXHTML .= '<h1 id="pdftitle">'. $x->post_title .'</h1>';
                        $strXHTML .= '<label id="pdfbyline">'. date('F d, Y', strtotime( $x->post_date )) .'  | The Wire Report </label>';
                        $strXHTML .= '<div id="pdfcontents">' . $contents . '</div>';
                        $strXHTML .= '</div>';  // end #pdfpagecontainer
                        
                        $intWordsCount = $intWordsCount + strlen($x->post_content);
                        if($intWordsCount > 3000){
                        $intWordsCount = 0;
                        }

                        elseif($intWordsCount > 1800){
                        $strXHTML .= '<div class="endofsection"></div>';
                        $intWordsCount = 0;
                        }
          }      
      } 

      //Briefs
      if(count($strBriefsContent) > 0){
          $intWordsCount = 0;
          foreach($strBriefsContent as $x){
            $contents = preg_replace('/<iframe.*?\/iframe>/i','', $x->post_content);
            $contents = wpautop( $contents );

            $strXHTML .= '<div id="pdfpagecontainer">';
            $strXHTML .= '<span id="pdflabel2">Briefs</span>';
            $strXHTML .= '<h1 id="pdftitle">'. $x->post_title .'</h1>';
            $strXHTML .= '<label id="pdfbyline">'. date('F d, Y', strtotime( $x->post_date )) .'  | The Wire Report </label>';
            $strXHTML .= '<div id="pdfcontents">' . $contents . '</div>';
            $strXHTML .= '</div>';  // end #pdfpagecontainer

            $intWordsCount = $intWordsCount + strlen($x->post_content);
            if($intWordsCount > 3000){
                $intWordsCount = 0;
            }

            elseif($intWordsCount > 1800){
                $strXHTML .= '<div class="endofsection"></div>';
                $intWordsCount = 0;
            }
          }
        }

      // Court
        if(count($strCourtContent) > 0) {
            $intWordsCount = 0;
            foreach($strCourtContent as $x){
                $contents = preg_replace('/<iframe.*?\/iframe>/i','', $x->post_content);
                $contents = wpautop( $contents );

                $strXHTML .= '<div id="pdfpagecontainer">';
                $strXHTML .= '<span id="pdflabel2">Court</span>';
                $strXHTML .= '<h1 id="pdftitle">'. $x->post_title .'</h1>';
                $strXHTML .= '<label id="pdfbyline">'. date('F d, Y', strtotime( $x->post_date )) .'  | The Wire Report </label>';
                $strXHTML .= '<div id="pdfcontents">' . $contents . '</div>';
                $strXHTML .= '</div>';  // end #pdfpagecontainer

                $intWordsCount = $intWordsCount + strlen($x->post_content);
                if($intWordsCount > 3000){
                    $intWordsCount = 0;
                }

                elseif($intWordsCount > 1800){
                    $strXHTML .= '<div class="endofsection"></div>';
                    $intWordsCount = 0;
                }
            }
        }   

      // Regulatory
      if(count($strRegulatoryContent) > 0){
        $intWordsCount = 0;
        foreach($strRegulatoryContent as $x){
            $contents = preg_replace('/<iframe.*?\/iframe>/i','', $x->post_content);
            $contents = wpautop( $contents );

            $strXHTML .= '<div id="pdfpagecontainer">';
            $strXHTML .= '<span id="pdflabel2">Regulatory</span>';
            $strXHTML .= '<h1 id="pdftitle">'. $x->post_title .'</h1>';
            $strXHTML .= '<label id="pdfbyline">'. date('F d, Y', strtotime( $x->post_date )) .'  | The Wire Report </label>';
            $strXHTML .= '<div id="pdfcontents">' . $contents . '</div>';
            $strXHTML .= '</div>';  // end #pdfpagecontainer

            $intWordsCount = $intWordsCount + strlen($x->post_content);
            if($intWordsCount > 3000){
                $intWordsCount = 0;
            }

            elseif($intWordsCount > 1800){
                $strXHTML .= '<div class="endofsection"></div>';
                $intWordsCount = 0;
            }
        }
    }

      // Broadcast
    if(count($strBroadcastContent) > 0){
        $intWordsCount = 0;
        foreach($strBroadcastContent as $x){
            $contents = preg_replace('/<iframe.*?\/iframe>/i','', $x->post_content);
            $contents = wpautop( $contents );

            $strXHTML .= '<div id="pdfpagecontainer">';
            $strXHTML .= '<span id="pdflabel2">Regulatory</span>';
            $strXHTML .= '<h1 id="pdftitle">'. $x->post_title .'</h1>';
            $strXHTML .= '<label id="pdfbyline">'. date('F d, Y', strtotime( $x->post_date )) .'  | The Wire Report </label>';
            $strXHTML .= '<div id="pdfcontents">' . $contents . '</div>';
            $strXHTML .= '</div>';  // end #pdfpagecontainer

            $intWordsCount = $intWordsCount + strlen($x->post_content);
            if($intWordsCount > 3000){
                $intWordsCount = 0;
            }

            elseif($intWordsCount > 1800){
                $strXHTML .= '<div class="endofsection"></div>';
                $intWordsCount = 0;
            }
        }
    }

      // Telecom
    if(count($strTelecomContent) > 0){
        $intWordsCount = 0;
        foreach($strTelecomContent as $x){
            $contents = preg_replace('/<iframe.*?\/iframe>/i','', $x->post_content);
            $contents = wpautop( $contents );

            $strXHTML .= '<div id="pdfpagecontainer">';
            $strXHTML .= '<span id="pdflabel2">Telecom</span>';
            $strXHTML .= '<h1 id="pdftitle">'. $x->post_title .'</h1>';
            $strXHTML .= '<label id="pdfbyline">'. date('F d, Y', strtotime( $x->post_date )) .'  | The Wire Report </label>';
            $strXHTML .= '<div id="pdfcontents">' . $contents . '</div>';
            $strXHTML .= '</div>';  // end #pdfpagecontainer

            $intWordsCount = $intWordsCount + strlen($x->post_content);
            if($intWordsCount > 3000){
                $intWordsCount = 0;
            }

            elseif($intWordsCount > 1800){
                $strXHTML .= '<div class="endofsection"></div>';
                $intWordsCount = 0;
            }
        }
    }
      // People
      if(count($strPeopleContent) > 0){
        $intWordsCount = 0;
        foreach($strPeopleContent as $x){
            $contents = preg_replace('/<iframe.*?\/iframe>/i','', $x->post_content);
            $contents = wpautop( $contents );

            $strXHTML .= '<div id="pdfpagecontainer">';
            $strXHTML .= '<span id="pdflabel2">People</span>';
            $strXHTML .= '<h1 id="pdftitle">'. $x->post_title .'</h1>';
            $strXHTML .= '<label id="pdfbyline">'. date('F d, Y', strtotime( $x->post_date )) .'  | The Wire Report </label>';
            $strXHTML .= '<div id="pdfcontents">' . wirereport_tidy_pdf_content( $contents ) . '</div>';
            $strXHTML .= '</div>';  // end #pdfpagecontainer

            $intWordsCount = $intWordsCount + strlen($x->post_content);
            if($intWordsCount > 3000){
                $intWordsCount = 0;
            }

            elseif($intWordsCount > 1800){
                $strXHTML .= '<div class="endofsection"></div>';
                $intWordsCount = 0;
            }
        }
    }

    $strXHTML .= '<div class="endofsection"></div>'; 
    $strXHTML .= '</div>'; // end #page-inside-layout    
    $strXHTML .= '</body></html>';

    echo $strXHTML;
    ?>
</body>
</html>