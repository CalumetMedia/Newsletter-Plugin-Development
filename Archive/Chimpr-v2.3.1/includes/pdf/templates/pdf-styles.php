<?php
// PDF-specific styles that will be included in both preview and final PDF
?>
<style>
    .pdf-preview {
        background: white;
        padding: 20px;
        max-width: 800px;
        margin: 0 auto;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .pdf-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .pdf-title {
        font-size: 24px;
        font-weight: bold;
        margin: 20px 0;
    }

    .pdf-date {
        color: #666;
        font-size: 14px;
    }

    .pdf-content {
        font-size: 12px;
        line-height: 1.6;
    }

    .pdf-section {
        margin-bottom: 30px;
        page-break-inside: avoid;
    }

    .pdf-section-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 15px;
        color: #333;
    }

    @media print {
        .pdf-preview {
            box-shadow: none;
            padding: 0;
        }
    }
</style>