<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Hill Time Research</title>
    <style type="text/css">
        /* /\/\/\/\/\/\/\/\/ CLIENT-SPECIFIC STYLES /\/\/\/\/\/\/\/\/ */
        #outlook a {
            padding: 0;
        }

        /* Force Outlook to provide a "view in browser" message */
        .ReadMsgBody {
            width: 100%;
        }

        .ExternalClass {
            width: 100%;
        }

        /* Force Hotmail to display emails at full width */
        .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {
            line-height: 100%;
        }

        /* Force Hotmail to display normal line spacing */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        /* Prevent WebKit and Windows mobile changing default text sizes */
        table, td {
            mso-table-lspace: 0;
            mso-table-rspace: 0;
        }

        /* Remove spacing between tables in Outlook 2007 and up */
        img {
            -ms-interpolation-mode: bicubic;
        }

        /* Allow smoother rendering of resized image in Internet Explorer */
        /* /\/\/\/\/\/\/\/\/ RESET STYLES /\/\/\/\/\/\/\/\/ */
        body {
            margin: 0;
            padding: 0;
        }

        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        table {
            border-collapse: collapse !important;
        }

        body, #bodyTable, #bodyCell {
            height: 100% !important;
            margin: 0;
            padding: 0;
            width: 100% !important;
        }

        /* /\/\/\/\/\/\/\/\/ TEMPLATE STYLES /\/\/\/\/\/\/\/\/ */
        /* ========== Page Styles ========== */
        .bodyCell {
            padding: 20px;
        }

        .templateContainer {
            width: 600px;
        }

        /**
        * @tab Page
        * @section background style
        * @tip Set the background color and top border for your email. You may want to choose colors that match your company's branding.
        * @theme page
        */
        body, .bodyTable {
            background-color: #FFF;
        }

        /**
        * @tab Page
        * @section background style
        * @tip Set the background color and top border for your email. You may want to choose colors that match your company's branding.
        * @theme page
        */
        .bodyCell {
            border-top: 4px solid #BBBBBB;
        }

        /**
        * @tab Page
        * @section email border
        * @tip Set the border for your email.
        */
        .templateContainer {
            border: 0 solid #BBBBBB;
        }

        /**
        * @tab Page
        * @section heading 1
        * @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
        * @style heading 1
        */
        h1 {
            color: #202020 !important;
            display: block;
            font-family: Georgia, serif;
            font-size: 20px;
            font-style: normal;
            font-weight: bold;
            line-height: 100%;
            letter-spacing: normal;
            margin: 0 0 10px;
            text-align: left;
        }

        /**
        * @tab Page
        * @section heading 2
        * @tip Set the styling for all second-level headings in your emails.
        * @style heading 2
        */
        h2 {
            color: #404040 !important;
            display: block;
            font-family: Helvetica, serif;
            font-size: 20px;
            font-style: normal;
            font-weight: bold;
            line-height: 100%;
            letter-spacing: normal;
            margin: 0 0 10px;
            text-align: left;
        }

        /**
        * @tab Page
        * @section heading 3
        * @tip Set the styling for all third-level headings in your emails.
        * @style heading 3
        */
        h3 {
            color: #606060 !important;
            display: block;
            font-family: Helvetica, serif;
            font-size: 16px;
            font-style: italic;
            font-weight: normal;
            line-height: 100%;
            letter-spacing: normal;
            margin: 0 0 10px;
            text-align: left;
        }

        /**
        * @tab Page
        * @section heading 4
        * @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
        * @style heading 4
        */
        h4 {
            color: #808080 !important;
            display: block;
            font-family: Helvetica, serif;
            font-size: 14px;
            font-style: italic;
            font-weight: normal;
            line-height: 100%;
            letter-spacing: normal;
            margin: 0 0 10px;
            text-align: left;
        }

        /* ========== Header Styles ========== */
        /**
        * @tab Header
        * @section preheader style
        * @tip Set the background color and bottom border for your email's preheader area.
        * @theme header
        */
        .templatePreheader {
            background-color: #F4F4F4;
            border-bottom: 1px solid #CCCCCC;
        }

        /**
        * @tab Header
        * @section preheader text
        * @tip Set the styling for your email's preheader text. Choose a size and color that is easy to read.
        */
        .preheaderContent {

            color: #808080;
            font-family: Helvetica, serif;
            font-size: 14px;
            line-height: 125%;
            text-align: left;
        }

        /**
        * @tab Header
        * @section preheader link
        * @tip Set the styling for your email's preheader links. Choose a color that helps them stand out from your text.
        */
        .preheaderContent a:link, .preheaderContent a:visited, /* Yahoo! Mail Override */
        .preheaderContent a .yshortcuts /* Yahoo! Mail Override */
        {
            color: #606060;
            font-weight: normal;
            text-decoration: underline;
        }

        /**
        * @tab Header
        * @section header style
        * @tip Set the background color and borders for your email's header area.
        * @theme header
        */
        .templateHeader {

            background-color: #F4F4F4;
            border-top: 1px solid #FFFFFF;
            border-bottom: 1px solid #CCCCCC;
        }

        /**
        * @tab Header
        * @section header text
        * @tip Set the styling for your email's header text. Choose a size and color that is easy to read.
        */
        .headerContent {
            color: #505050;
            font-family: Helvetica, serif;
            font-size: 20px;
            font-weight: bold;
            line-height: 100%;
            padding: 0;
            vertical-align: middle;
        }

        .headerContent img {
            margin: auto;
        }

        /**
        * @tab Header
        * @section header link
        * @tip Set the styling for your email's header links. Choose a color that helps them stand out from your text.
        */
        .headerContent a:link, .headerContent a:visited, /* Yahoo! Mail Override */
        .headerContent a .yshortcuts /* Yahoo! Mail Override */
        {
            color: #EB4102;
            font-weight: normal;
            text-decoration: underline;
        }

        .headerImage {
            height: auto;
            max-width: 600px;
            margin: auto;
        }

        .headerImage img {
            height: auto;
            max-width: 600px;
            margin: auto;
        }

        /* ========== Body Styles ========== */
        /**
        * @tab Body
        * @section body style
        * @tip Set the background color and borders for your email's body area.
        */
        .templateBody {
            background-color: #F4F4F4;
            border-top: 1px solid #FFFFFF;
            border-bottom: 1px solid #CCCCCC;
        }

        /**
        * @tab Body
        * @section body text
        * @tip Set the styling for your email's main content text. Choose a size and color that is easy to read.
        * @theme main
        */
        .bodyContent {
            color: #505050;
            font-family: Helvetica, serif;
            font-size: 16px;
            line-height: 150%;
            padding: 20px;
            text-align: left;
        }

        /**
        * @tab Body
        * @section body link
        * @tip Set the styling for your email's main content links. Choose a color that helps them stand out from your text.
        */
        .bodyContent a:link, .bodyContent a:visited, /* Yahoo! Mail Override */
        .bodyContent a .yshortcuts /* Yahoo! Mail Override */
        {
            color: black;
            font-weight: normal;
            text-decoration: underline;
        }

        .bodyContent img {
            display: inline;
            height: auto;
            max-width: 560px;
        }

        /**
        * @tab Body
        * @section body subhead
        * @tip Set the styling for your email's main content text. Choose a size and color that is easy to read.
        * @theme main
        */
        .subheadnormal {
            color: white;
            font-family: Georgia, serif;
            font-size: 20px;
            text-align: left;
        }

        .subheadnormal img {
            float: right;
        }

        .subheadpolitics {
            color: black;
            font-family: Helvetica, serif;
            font-size: 22px;
            line-height: 150%;
            background-color: inherit;
            padding: 5px 5px 5px 20px;
            text-align: left;
        }

        .subheadpolitics h5 {
            margin-top: 5px !important;
            margin-bottom: 5px !important;
        }

        .subheadpolitics img {
            float: right;
        }

        /* ========== Column Styles ========== */
        .templateColumnContainer {
            width: 260px;
        }

        /**
        * @tab Columns
        * @section column style
        * @tip Set the background color and borders for your email's column area.
        */
        .templateColumns {
            background-color: #F4F4F4;
            border-top: 1px solid #FFFFFF;
            border-bottom: 1px solid #CCCCCC;
        }

        .templateColumns-readmore {
            background-color: #DEE0E2;
        }

        /**
        * @tab Columns
        * @section left column text
        * @tip Set the styling for your email's left column content text. Choose a size and color that is easy to read.
        */
        .leftColumnContent {
            color: #505050;
            font-family: Helvetica, serif;
            font-size: 14px;
            line-height: 150%;
            padding: 0 20px 20px;
            text-align: left;
        }

        /**
        * @tab Columns
        * @section left column link
        * @tip Set the styling for your email's left column content links. Choose a color that helps them stand out from your text.
        */

        /**
        * @tab Columns
        * @section right column text
        * @tip Set the styling for your email's right column content text. Choose a size and color that is easy to read.
        */
        .rightColumnContent {
            color: #505050;
            font-family: Georgia, serif;
            font-size: 14px;
            line-height: 150%;
            padding: 0 5px 20px 20px;
            text-align: left;
        }

        /**
        * @tab Columns
        * @section right column link
        * @tip Set the styling for your email's right column content links. Choose a color that helps them stand out from your text.
        */
        .rightColumnContent a:link, .rightColumnContent a:visited, /* Yahoo! Mail Override */
        .rightColumnContent a .yshortcuts /* Yahoo! Mail Override */
        {
            color: black;
            font-weight: normal;
            text-decoration: underline;
        }

        .leftColumnContent img, .rightColumnContent img {
            display: inline;
            height: auto;
            max-width: 220px;
        }

        /* ========== Footer Styles ========== */
        /**
        * @tab Footer
        * @section footer style
        * @tip Set the background color and borders for your email's footer area.
        * @theme footer
        */
        #templateFooter {
            background-color: #F4F4F4;
            border-top: 1px solid #FFFFFF;
        }

        /**
        * @tab Footer
        * @section footer text
        * @tip Set the styling for your email's footer text. Choose a size and color that is easy to read.
        * @theme footer
        */
        .footerContent {
            color: #808080;
            font-family: Helvetica, serif;
            font-size: 10px;
            line-height: 150%;
            padding: 20px;
            text-align: left;
        }

        .readmore a {
            font-family: Helvetica, serif;
            background-color: #004284;
            color: white;
            padding: 7px 10px 5px;
            text-decoration: none !important;
            border-top: none !important;
            text-transform: uppercase;
        }

        .readmore {
            margin-top: 20px !important;
        }

        /**
        * @tab Footer
        * @section footer link
        * @tip Set the styling for your email's footer links. Choose a color that helps them stand out from your text.
        */
        .footerContent a:link, .footerContent a:visited, /* Yahoo! Mail Override */
        .footerContent a .yshortcuts, .footerContent a span /* Yahoo! Mail Override */
        {
            color: #606060;
            font-weight: normal;
            text-decoration: underline;
        }

        /* /\/\/\/\/\/\/\/\/ MOBILE STYLES /\/\/\/\/\/\/\/\/ */
        @media only screen and (max-width: 480px) {
            /* /\/\/\/\/\/\/ CLIENT-SPECIFIC MOBILE STYLES /\/\/\/\/\/\/ */
            body, table, td, p, a, li, blockquote {
                -webkit-text-size-adjust: none !important;
            }

            /* Prevent Webkit platforms from changing default text sizes */
            body {
                width: 100% !important;
                min-width: 100% !important;
            }

            /* Prevent iOS Mail from adding padding to the body */
            /* /\/\/\/\/\/\/ MOBILE RESET STYLES /\/\/\/\/\/\/ */
            .bodyCell {
                padding: 10px !important;
            }

            /* /\/\/\/\/\/\/ MOBILE TEMPLATE STYLES /\/\/\/\/\/\/ */
            /* ======== Page Styles ======== */
            /**
            * @tab Mobile Styles
            * @section template width
            * @tip Make the template fluid for portrait or landscape view adaptability. If a fluid layout doesn't work for you, set the width to 300px instead.
            */
            .templateContainer {
                max-width: 600px !important;
                width: 100% !important;
            }

            /**
            * @tab Mobile Styles
            * @section heading 1
            * @tip Make the first-level headings larger in size for better readability on small screens.
            */
            h1 {
                font-size: 24px !important;
                padding-bottom: 0 !important;
                margin-bottom: 0 !important;
                line-height: 100% !important;
            }

            h1 a {
                font-size: 24px !important;
                padding-bottom: 0 !important;
                margin-bottom: 0 !important;
                line-height: 100% !important;
            }

            /**
            * @tab Mobile Styles
            * @section heading 2
            * @tip Make the second-level headings larger in size for better readability on small screens.
            */
            h2 {
                font-size: 20px !important;
                line-height: 100% !important;
            }

            /**
            * @tab Mobile Styles
            * @section heading 3
            * @tip Make the third-level headings larger in size for better readability on small screens.
            */
            h3 {
                font-size: 18px !important;
                line-height: 100% !important;
            }

            /**
            * @tab Mobile Styles
            * @section heading 4
            * @tip Make the fourth-level headings larger in size for better readability on small screens.
            */
            h4 {
                font-size: 16px !important;
                line-height: 100% !important;
            }

            /* ======== Header Styles ======== */
            .templatePreheader {
                display: none !important;
            }

            /* Hide the template preheader to save space */
            /**
            * @tab Mobile Styles
            * @section header image
            * @tip Make the main header image fluid for portrait or landscape view adaptability, and set the image's original width as the max-width. If a fluid setting doesn't work, set the image width to half its original size instead.
            */
            .headerImage {
                height: auto !important;
                max-width: 600px !important;
                width: 100% !important;
            }

            /**
            * @tab Mobile Styles
            * @section header text
            * @tip Make the header content text larger in size for better readability on small screens. We recommend a font size of at least 16px.
            */
            .headerContent {
                font-size: 20px !important;
                line-height: 125% !important;
            }

            /* ======== Body Styles ======== */
            /**
            * @tab Mobile Styles
            * @section body text
            * @tip Make the body content text larger in size for better readability on small screens. We recommend a font size of at least 16px.
            */
            .bodyContent {
                font-size: 18px !important;
                line-height: 125% !important;
            }

            .date h5 {
                font-family: Georgia, serif !important
                color: #99a3a4;
                margin: auto !important;
                margin-top: inherit !important;
                margin-bottom: inherit !important;
            }

            /* ======== Column Styles ======== */
            .templateColumnContainer {
                display: block !important;
                width: 100% !important;
            }

            .templateColumnContainer {
                padding-top: 5px !important;
            }

            /**
            * @tab Mobile Styles
            * @section column image
            * @tip Make the column image fluid for portrait or landscape view adaptability, and set the image's original width as the max-width. If a fluid setting doesn't work, set the image width to half its original size instead.
            */
            .columnImage {
                height: auto !important;
                max-width: 480px !important;
                width: 100% !important;
            }

            .lockImage {
                height: auto !important;
                max-width: 25px !important;
                width: 100% !important;
            }

            /**
            * @tab Mobile Styles
            * @section left column text
            * @tip Make the left column content text larger in size for better readability on small screens. We recommend a font size of at least 16px.
            */
            .leftColumnContent {
                font-size: 16px !important;
                line-height: 125% !important;
            }

            /**
            * @tab Mobile Styles
            * @section right column text
            * @tip Make the right column content text larger in size for better readability on small screens. We recommend a font size of at least 16px.
            */
            .rightColumnContent {
                font-size: 16px !important;
                line-height: 125% !important;
                padding-top: 15px !important;
            }

            /* ======== Footer Styles ======== */
            /**
            * @tab Mobile Styles
            * @section footer text
            * @tip Make the body content text larger in size for better readability on small screens.
            */
            .footerContent {
                font-size: 14px !important;
                line-height: 115% !important;
            }

            .footerContent a {
                display: block !important;
            }

            /* Place footer social and utility links on their own lines, for easier access */
        }

        @media only screen and (max-width: 480px) {
            table#canspamBar td {
                font-size: 14px !important;
            }

            table#canspamBar td a {
                display: block !important;
                margin-top: 10px !important;
            }

            .templateContainer {
                float: none !important;
                width: 90% !important;
                margin: 0 auto !important;
                display: table !important;
                border: 1px solid #F00;
            }
        }
    </style>
</head>
<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
<center>
    <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="90%" class="bodyTable"
           style="font-family: Helvetica;">
        <tr>
            <td align="center" valign="top" class="bodyCell" style="border-top: 0px solid #BBBBBB;">
                <!-- BEGIN TEMPLATE // -->
                <table border="0" cellpadding="0" cellspacing="0" width="600" class="templateContainer">
                    <tr>
                        <td align="center" valign="top">
                            <!-- BEGIN PREHEADER // -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templatePreheader"
                                   style="background-color: #FFF; border: 0px;">
                                <tr>
                                    <td valign="top" class="preheaderContent"
                                        style="padding-top: 20px; padding-right:20px; padding-bottom: 20px; padding-left:20px; background: #000; border: 0px;">
                                        <center><a href="#"><img
                                                        src="https://thewirereport.ca/wp-content/themes/wirereport/img/wr-logoblack-new-reverse.png"
                                                        style="width: 350px;"/></a></center>
                                    </td>
                                </tr>
                            </table>
                            <!-- // END PREHEADER -->
                        </td>
                    </tr>

                    <tr>
                        <td align="center" valign="top" style="height: 50px;"></td>
                    </tr>

                    <tr>
                        <td align="center" valign="top" style="height: 20px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" valign="top"
                                        style="font-style: italic; font-size: 11pt; color: #000; text-align: left">Login
                                        to <a href="https://thewirereport.ca/login-to-wirereport-ca/"
                                              style="color: #BF5E15; text-decoration: none;">The Wire Report</a> to read
                                        all these stories.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" valign="top" style="padding-top: 15px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" valign="top">
                                        <?php
                                        if (isset($posts)) {
                                            if (count($posts) > 0) { ?>
                                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td height="40" valign="middle" class="subheadnormal"
                                                            style="background: #FFF; padding: 0px; color: #000000; font-family: Helvetica;  text-align: left;">
                                                            <ul style="margin: 0; padding: 20px 0px; line-height: 1em;  margin-left: -20px;">
                                                                <?php
                                                                foreach ($posts as $x) {
                                                                    echo '<li style="width: 100%; list-style: none; list-style-position: inside; padding-bottom: 25px;"><a href="' . get_permalink($x->ID) . '" style="color: #000; font-size: 11pt;">' . $x->post_title . '</a></li>';
                                                                }
                                                                ?>
                                                            </ul>
                                                        </td>
                                                    </tr>
                                                </table>
                                            <?php }
                                        } ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" valign="top">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%"
                                   style="border-bottom: 2px solid #000;">
                                <tr>
                                    <td height="40" valign="middle" class="subheadnormal"
                                        style="text-align: left; background: #FFF; padding: 10px 0px 5px 0px; color: #000000; font-family: Helvetica; font-size: 25px; font-weight: bold; text-transform: uppercase;">
                                        The Wire Report Exclusive PDF
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" valign="top">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td height="40" valign="middle" class="subheadnormal"
                                                    style="background: #FFF; padding: 0px; color: #000000; font-family: Helvetica;  text-align: left; padding-bottom: 100px;">
                                                    <ul style="margin: 0; padding: 20px 0px; margin-left: -20px;">
                                                        <li style="width: 100%; list-style: none; list-style-position: inside; padding-bottom: 25px;">
                                                            <a href="<?php echo $pdfURL; ?>"
                                                               style="text-decoration: underline; color: #000; font-size: 11pt !important; font-weight: bold;"><?php echo 'Download ' . date(' F d, Y', strtotime('today')); ?>
                                                                PDF</a>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </table>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td height="40" valign="middle" class="subheadnormal"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <!-- // END TEMPLATE -->
            </td>
        </tr>

        <tr>
            <td align="center" style="font-family:Arial,Helvetica,sans-serif; font-size: 12px; padding-bottom: 5px;">
                <div bgcolor="#FFF" style="background-color: #333333; text-align: center; padding:30px; ">
                    <div bgcolor="#e5e5e5"
                         style="background-color: #333333; color: #333; font-family: arial,helvetica,sans-serif; font-size: 11px; line-height: 17px; ">
                        <table bgcolor="#333333"
                               style="margin-bottom:25px; margin: 0 auto; display: table; color: #FFF;">
                            <tbody>
                            <tr>
                                <td bgcolor="#333333" align="center" colspan="2"
                                    style="font-size: 12px; font-family: arial,helvetica,sans-serif; line-height: 22px ">
                                    <div>
                                        <label style="color: #FFF; margin: 0 0 11px; font-size: 12px; font-weight: bold;">About
                                            This Email</label>
                                        <p style="color: #FFF; margin: 10px 0 0; ">You received this message because you
                                            signed up for <em>The Wire Report</em>.</p>
                                        <p style="color: #FFF; margin: 5px 0 0; ">Forgot your username or password?
                                            Contact Subscriber Services at <a href="" target="_top"
                                                                              style="color: #FFF;">circulation@hilltimes.com</a>
                                            or at 613-288-1146.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor="#333333" align="center"
                                    style="display: block; font-family: arial,helvetica,sans-serif; font-size: 11px; padding: 10px 0 17px 0; ">
                                    <div style="border-top: 1px solid rgb(226, 226, 226);">
                                        <div style="color: #000; font-family: arial,helvetica,san s-serif; font-size: 12px; height: 20px; padding-top: 7px;">
                                            <span style="color:#909090; font-family:arial,helvetica,sans serif; font-size:10px">Copyright <?php echo date("Y"); ?></span>
                                            <span style="color:#FFF; font-size:10px; padding-right:2px">|</span> <span
                                                    style="color:#FFF; font-family:arial,helvetica,sans serif; font-size:10px">The Wire Report</span>
                                            <span style="color:#FFF; font-size:10px; padding:0 5px 0 2px">|</span> <span
                                                    style="color:#FFF; font-family:arial,helvetica,sans serif; font-size:10px">246 Queen St. · Suite 200 · Ottawa, ON K1P 5E4 · Canada </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</center>
<center>
    <table border="0" cellpadding="0" cellspacing="0" width="100%" id="canspamBarWrapper"
           style="background-color:#FFFFFF; border-top:1px solid #E5E5E5;">
        <tr>
            <td align="center" valign="top" style="padding-top:20px; padding-bottom:20px;">
                <table border="0" cellpadding="0" cellspacing="0" id="canspamBar">
                    <tr>
                        <td align="center" valign="top"
                            style="color:#606060; font-family:Helvetica, Arial, sans-serif; font-size:11px; line-height:150%; padding-right:20px; padding-bottom:5px; padding-left:20px; text-align:center;">
                            This email was sent to <a href="mailto:*|EMAIL|*" target="_blank"
                                                      style="color:#404040 !important;">*|EMAIL|*</a>
                            <br>
                            <a href="*|ABOUT_LIST|*" target="_blank" style="color:#404040 !important;"><em>why did I get
                                    this?</em></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="*|UNSUB|*"
                                                                             style="color:#404040 !important;">unsubscribe
                                from this list</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="*|UPDATE_PROFILE|*"
                                                                             style="color:#404040 !important;">update
                                subscription preferences</a>
                            <br>
                            Hill Times Publishing · 246 Queen Street, Suite 200 · Ottawa, On K1P 5E4 · Canada
                            <br>
                            <br>

                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</center>
</body>
</html>