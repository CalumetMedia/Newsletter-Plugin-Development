<html>
    <head>
        <script>
            function subst() {
                var vars = {};
                var urlVars = document.location.search.substring(1).split('&');
                for (var urlVar in urlVars) {
                    var varParts = urlVars[urlVar].split('=', 2);
                    vars[varParts[0]] = unescape(varParts[1]);
                }
                
                // First page has no header
                if (1 == vars['page']) {
                    document.getElementById('tbl-header').style.display = 'none';
                }
                // All other pages do
                else {
                    var pageVars = ['frompage', 'topage', 'page', 'webpage', 'subsection', 'subsubsection'];
                    for(var pageVar in pageVars) {
                        var pageVarElements = document.getElementsByClassName(pageVars[pageVar]);
                        
                        for(var j=0; j < pageVarElements.length; ++j) {
                            pageVarElements[j].textContent = vars[pageVars[pageVar]];
                        }
                    }
                    
                    months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    
                    today = new Date();
                    document.getElementById('date').textContent = ("" + months[today.getMonth()] + " " + today.getDate() + ", " + today.getFullYear());
                }
            }
        </script>
        <style>
            #tbl-header {
                border-bottom:  1px solid black;
                width:          100%;
                font-size:      90%;
            }
        </style>
    </head>
    <body style="border:0; margin: 0;" onload="subst()">
        <table id="tbl-header">
            <tr>
                <td>The Wire Report - <span id="date"></span></td>
                <td style="text-align:right">
                    Page <span class="page"></span> of <span class="topage"></span>
                </td>
            </tr>
        </table>
    </body>
</html>