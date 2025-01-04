<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{NEWSLETTER_NAME}</title>
    {PDF_STYLES}
</head>
<body>
    <div class="pdf-header">
        <img src="{HEADER_LOGO}" alt="Newsletter Logo" />
        <div class="pdf-date">{DATE}</div>
    </div>

    <div class="pdf-content">
        {CONTENT}
    </div>

    <div class="pdf-footer">
        <div class="page-number">Page {PAGE_NUM} of {PAGE_COUNT}</div>
    </div>
</body>
</html>