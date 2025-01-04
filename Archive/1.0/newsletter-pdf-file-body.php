<?php

  $pba = '';
  
?>
<html>
<head>
    <meta charset="UTF-8" />
    <!-- YUI Stylesheet reset -->
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.8.0r4/build/reset-fonts-grids/reset-fonts-grids.css" />
    
    <style>
    	/*
        h1, h2, h3, h4, p {
            page-break-inside: avoid !important;
        }
        
        h1, h2, h3, h4 {
            page-break-after: avoid;
        } */
        
        h1 {
            font-size:      250%;
            font-family:    serif;
            font-weight:    bold;
            text-align:     center;
            color:          #d65d23;
            margin:         0px;
        }
        
        h2 {
            font-family:    sans-serif;
            font-weight:    bold;
        }
        
        hr {
            border:     0px;
            border-top: solid 1px #000;
        }
        
        body {
            margin:         0px;
            text-align:     left;
            font-family:    serif;
            font-size:      85%;
        }
        
        p {
            margin:     16px 0px;
            font-size:  125%;
        }
        
        b, strong {
            font-weight: bold;
        }
        
        i, em {
            font-style: italic;
        }
        
        /* Masthead -----------------------------------------------------------------------------*/
        
        #masthead {
            width: 100%;
        }
        
        #masthead .issue_date {
            text-align:     right;
            vertical-align: bottom;
            font-family:    serif;
        }
        
        #masthead .issue_date h3 {
            margin: 0px;
            font-size:      125%;
            font-weight:    bold;
        }
        
        #masthead .issue_date h4 {
            margin: 0px;
            font-weight:    bold;
        }
        
        /* Table of Contents --------------------------------------------------------------------*/
        
        #toc {
            page-break-after:   always;
            font-family:        serif;
        }
        
        #toc td {
            vertical-align:     top;
            padding:            10px;
        }
        
        #toc h2 {
            font-family:    sans-serif;
            font-weight:    bold;
            margin-top:     0px;
            margin-bottom:  10px;
            font-size:      150%;
        }
        
        #toc ul {
            padding-left:   20px;
            margin-top:     0px;
        }
        
        #toc li {
            padding-bottom: 5px;
            list-style:     disc;
        }
        
        #toc li span {
            color:          #d65d23;
        }
        
        #toc a {
            color:              #000;
            text-decoration:    none;
        }
        
        #toc .middle {
            width:  10px;
        }
        
        #toc .right {
            width:              50%;
            background-color:   #ddd;
        }
        
        #toc .right h2 {
            color:          #d65d23;
        }
        
        /* Entries ------------------------------------------------------------------------------*/
        
        #entries h1 {
            margin-bottom: -20px;
        }
        
        #entries h2 {
            font-family:    sans-serif;
            margin:         0px;
            font-size:      150%;
        }
        
        .short-section {
            page-break-inside:  avoid;
        }
        
        .entry {
            margin-top:     20px;
            font-family:    "Times New Roman", serif;
            font-size:      85%;
            padding:        10px;
        }
        
        .entry.long {
            page-break-after:   always;
            padding-left:       20px;
            padding-right:      20px;
        }
        .breakbody.long {
            page-break-after:   always;
            padding-left:       20px;
            padding-right:      20px;
        }
        
        .entry.short-first {
            page-break-inside: auto !important;
        }
        
        .entry.short {
            border:             solid 1px #999;
            page-break-inside:  avoid;
            padding:            20px;
        }
        
        .entry .byline {
            margin-top:     5px;
            margin-bottom:  5px;
            font-size:      90%;
        }
        
        .entry .byline span {
            color:          #d65d23;
        }
    </style>

</head>
<body>
    <table id="masthead" style="border-bottom:1px #000 solid; padding-bottom:10px;">
        <tr>
            <td style="padding-bottom:10px; padding-top:10px; padding-left:10px;"><img src="http://wirereport.ca/wp-content/themes/wirereport/img/wr-logoblack-new.png" border="0" height="50px" /></td>
            <td style="padding-bottom:10px;" class="issue_date">
             <h3><?php echo date("F j, Y"); ?></h3>
             <h4>TheWireReport.ca</h4>
            </td>
        </tr>
    </table>
        
<?php         
  
  $strXHTML  = '<h1 style="padding-top:10px;padding-bottom:10px;">Table of Contents</h1>';    
  $strXHTML .= '<table id="toc">';
  $strXHTML .= '<tr>';
  
  $strXHTML .= '<td class="left">';               
  if(isset($strNewsContent))
  {
     $strXHTML .= '<h2>News</h2>';
     $strXHTML .= '<ul>';
   
     foreach($strNewsContent as $x)
       $strXHTML .= '<li><a href="#'.get_permalink($x->ID).'">'.$x->post_title.'</a></li>';                        
   
     $strXHTML .= '</ul>';
  } 
  $strXHTML .= '</td>';
  
  $strXHTML .= '<td class="middle"> </td>';
  
  $strXHTML .= '<td class="right">';            
  if(isset($strBriefsContent))
  {
     $strXHTML .= '<h2>Briefs</h2>';
     $strXHTML .= '<ul>';
     
     foreach($strBriefsContent as $x)
       $strXHTML .= '<li><a href="#'.get_permalink($x->ID).'">'.$x->post_title.'</a></li>';
     
     $strXHTML .= '</ul>';
  }   
  
  if(isset($strPeopleContent))
  {
     $strXHTML .= '<h2>People</h2>';
     $strXHTML .= '<ul>';
     
     foreach($strPeopleContent as $x)
       $strXHTML .= '<li><a href="#'.get_permalink($x->ID).'">'.$x->post_title.'</a></li>';
                    
     $strXHTML .= '</ul>';
  }  
  $strXHTML .= '</td>';
  
  $strXHTML .= '</tr>';
  $strXHTML .= '</table>';
  
  echo $strXHTML;

?> 
    
    <div id="entries">
        <h1><?php echo 'This Week in The Wire Report'; ?></h1>
        
        <?php foreach($strNewsContent as $x): ?>
            <div class="entry long pba">
                <h2><a name="<?php echo get_permalink($x->ID); ?>"><?php echo $x->post_title; ?></a></h2>
                <p class="byline"><b><?php echo $x->post_date; ?>  |  The Wire Report  </b></p>
                <hr />
                <p>  <?php echo nl2br($x->post_content); ?></p>
            </div>
            <?php $first = false; ?>
        <?php endforeach; ?>

        
        <?php if(count($strBriefsContent) > 0): ?>
            <div class="breakbody long">
                <h1>Briefs</h1>                
                <?php 
                $first = true; 
                $total = count($strBriefsContent);
                $i = 1;                                
                foreach($strBriefsContent as $x): 
             	   if($total > $i)
                  $pba = " pba";
                 else
                	$pba = "";                  
                ?>
                
                <div class="entry short<?php echo $first ? '-first' : '' ?> <?php echo $pba; ?>">
                <h2><a name="<?php echo get_permalink($x->ID); ?>"><?php echo $x->post_title; ?></a></h2>
                <p class="byline"><b><?php echo $x->post_date; ?>  |  The Wire Report  </b></p>
                <hr />
                <p><?php echo nl2br($x->post_content); ?></p>
                </div>
                
                <?php
                	$i++;
                  endforeach; 
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($strPeopleContent) > 0): ?>
		                <h1>People</h1>

            <div class="breakbody long pbb">
                
                <?php $first = true; 
                
                	$total = count($strPeopleContent);
                	$i = 1;                
                ?>
                <?php foreach($strPeopleContent as $x): 
             	if($total > $i)
                		$pba = " pba";
                	else
                		$pba= "";                    
                ?>
                    <div class="entry short<?php echo $first ? '-first' : ''; ?><?php echo $pba; ?>">
                        <h2><a name="<?php echo get_permalink($x->ID); ?>"><?php echo $x->post_title; ?></a></h2>
                        <p class="byline"><b><?php echo $x->post_date; ?>  |  The Wire Report  </b></p>
                        <hr />
                        <p><?php echo nl2br($x->post_content); ?></p>
                    </div>
                <?php 
                	$i++;
                endforeach; ?>
            </div>
        <?php endif; ?>
                     
    </div>
    
    <script type="text/php">
        
        if ( isset($pdf) ) {
            $w = $pdf->get_width();
            $h = $pdf->get_height();

            $text_width = $pdf->get_text_width("Page XX of YY", "arial", 10);
            
            $font = Font_Metrics::get_font("arial", "bold");
            $pdf->page_text($w - $text_width, 18, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 10, array(0,0,0));
            
            $pdf->page_text(10, 18, "The Wire Report - " . date("F j, Y"), $font, 10, array(0,0,0));
        }
        
    </script>
    
</body>
</html>