<html>
 <head>
 <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
      <style>
          div.page-layout { 
              width:205mm; 
              height:284.5mm; 
              overflow: hidden; 
          }

          #pagetopborder{ 
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

          #toc-left{
              float: left;
              width: 40%;
              height: 100%;
              margin-left: 4%;
          }

          #toc-left h1{
              float: left;
              width: 100%;
              padding: 8px 15px;
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
              margin-bottom: 10px;
              font-size: 12pt;
              border-bottom: 1px solid #231f20;
          }

          #toc-right{
              float: right;
              width: 40%;
              margin-right: 8%;
              height: 100%:
          }

          #toc-right ul li{
              list-style: none;
              padding: 10px 10px 10px 0px;
              margin-left: 10px;
              margin-bottom: 10px;
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
              padding: 8px 15px;
              background: #223033;
              color: #ff6b6d;
              font-size: 22pt;
          }

          #pdfpagecontainer span, h1, label, p{
              float: left;
              width: 100%:
          }

          #pdfpagecontainer span{
              font-size: 15pt;
              padding: 8px;
              background: #223033;
              color: #F49545;
              margin-bottom: 5px;
          }

          #pdfpagecontainer h1{
              font-size: 20pt;
              color: #223033;
          } 

          #pdfpagecontainer label{
              font-size: 10pt;
              color: #338D8D;    
          } 

          #pdfpagecontainer p{
              font-size: 11pt;
              line-height: 1.6em;
              color: #231F20;
              margin-bottom: 10px;
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

          #pdfcontents p{
              width: 100%;
              font-size: 10pt;
              line-height: 1.5em;
              margin-bottom: 25px;
          }

         .endofsection { page-break-after:always; }
      </style>
 </head>
 <body style="margin:0; padding:0;">

   <div class="page-layout" id="coverpage">
        <div id="pagetopborder"></div>  
        <div id="coverdate"><?php echo date("F j, Y"); ?></div>
        <div id="toctitle">Table of Contents</div>
        
        <?php 
           if(count($strNewsContent) > 0){ 
                $strXHTMLTOC .= '<div id="toc-left">';
                $strXHTMLTOC .= '<h1>News</h1><ul id="pdf-toc">';
                foreach($strNewsContent as $x){
                    $strXHTMLTOC .= '<li>'.$x->post_title.'</li>';  
                }
                $strXHTMLTOC .= '</ul></div>';
            }    
           
            if(count($strBriefsContent) > 0){ 
                $strXHTMLTOC .= '<div id="toc-right">';
                $strXHTMLTOC .= '<h1>Briefs</h1><ul id="pdf-toc">';
                foreach($strBriefsContent as $x){
                     $strXHTMLTOC .= '<li>'.$x->post_title.'</li>';
                }
                $strXHTMLTOC .= '</ul></div>';
            }   
            
            echo $strXHTMLTOC;
        ?>
    </div> 

    <?php
    //News
    if(count($strNewsContent) > 0){
          $strXHTML .= '<div class="page-layout">';
          $strXHTML .= '<div id="pagetopborder"></div>';
          
          $i = 1;
          $intWordsCount = 0;
          foreach($strNewsContent as $x){
              // fix problematic quote
                $strXHTML .= '<div id="pdfpagecontainer">';
                $strXHTML .= '<span style="color: #E79545;">NEWS</span>';
                $strXHTML .= '<h1>'. $x->post_title .'</h1>';
                $strXHTML .= '<label>'. $x->post_date .'  | The Wire Report </label>';
                $strXHTML .= '</div>';  // end #pdfpagecontainer
                
                //$strXHTML .= '<div id="pdfcontents">' . $x->post_content . '</div>';
               /* $intWordsCount = $intWordsCount + strlen($x->post_content);
                if($intWordsCount > 3000){
                    $intWordsCount = 0;
                }

                elseif($intWordsCount > 1800){
                    $strXHTML .= '<div class="endofsection"></div>';
                    $intWordsCount = 0;
                }*/
          }
          //$strXHTML .= '<div class="endofsection"></div>'; 
          $strXHTML .= '</div>'; // end #page-inside-layout
      } 

      //Briefs
      if(count($strBriefsContent) > 0){
          $strXHTML .= '<div class="page-layout">';
          $strXHTML .= '<div id="pagetopborder"></div>';
         
          $i = 1;
          $intWordsCount = 0;
          foreach($strBriefsContent as $x){
              $strXHTML .= '<div id="pdfpagecontainer">';
              $strXHTML .= '<span style="color:#FF6B6D;">Briefs</span>';
              $strXHTML .= '<h1>'. $x->post_title .'</h1>';
              $strXHTML .= '<label>'. $x->post_date .' | The Wire Report </label>';
              //$strXHTML .= '<div id="pdfcontents">' . $x->post_content . '</div>';
              $strXHTML .= '</div>';  // end #pdfpagecontainer

              /*  $intWordsCount = $intWordsCount + strlen($x->post_content);
                if($intWordsCount > 3000){
                    $intWordsCount = 0;
                }

                elseif($intWordsCount > 1800){
                    $strXHTML .= '<div class="endofsection"></div>';
                    $intWordsCount = 0;
                }*/
              
          }
          //$strXHTML .= '<div class="endofsection"></div>'; 
          $strXHTML .= '</div>'; // end #page-inside-layout
    }    

    $strXHTML .= '</div>';
    $strXHTML .= '</body></html>';

    echo $strXHTML;
    ?>
</body>
</html>