body,
body *:not(html):not(style):not(br):not(tr):not(code) {
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    position: relative;
}

body {
    -webkit-text-size-adjust: none;
    background-color: #f1f3f2;
    color: #4b5563;
    height: 100%;
    line-height: 1.45;
    margin: 0;
    padding: 0;
    width: 100% !important;
}

a {
    color: #00A651;
}

img {
    max-width: 100%;
}

h1 {
    color: #00A651;
    font-size: 25px;
    font-weight: 800;
    line-height: 1.08;
    margin: 0 0 20px;
    text-align: left;
}

h2 {
    color: #111827;
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 14px;
}

h3 {
    color: #111827;
    font-size: 15px;
    font-weight: 700;
    margin: 0 0 12px;
}

p,
ul,
ol,
blockquote {
    line-height: 1.5;
    text-align: left;
}

p {
    color: #4b5563;
    font-size: 15px;
    margin: 0 0 16px;
}

table {
    border-collapse: collapse;
}

.wrapper {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    background-color: #f1f3f2;
    margin: 0;
    padding: 0;
    width: 100%;
}

.content {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 0;
    padding: 0;
    width: 100%;
}

.body {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    background-color: #f1f3f2;
    border: 0;
    margin: 0;
    padding: 0;
    width: 100%;
}

.inner-body {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 570px;
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    box-shadow: none;
    margin: 16px auto 0;
    overflow: hidden;
    padding: 0;
    width: 570px;
}

.content-cell {
    max-width: 100vw;
    padding: 36px 46px 32px;
}

.mail-brand-header {
    background-color: #00A651;
    border-radius: 6px 6px 0 0;
    padding: 20px 46px;
}

.mail-brand-mark {
    color: #ffffff;
    display: inline-block;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 0;
    text-decoration: none;
}

.mail-brand-logo-wrap {
    display: inline-block;
}

.mail-brand-logo {
    border: 0;
    display: block;
    height: auto;
    max-height: 42px;
    max-width: 172px;
    outline: none;
    text-decoration: none;
}

.action {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 26px auto;
    padding: 0;
    text-align: left;
    width: 100%;
}

.button {
    -webkit-text-size-adjust: none;
    border-radius: 8px;
    color: #ffffff !important;
    display: inline-block;
    font-size: 14px;
    font-weight: 700;
    overflow: hidden;
    text-decoration: none;
}

.button-blue,
.button-primary,
.button-green,
.button-success {
    background-color: #00A651;
    border-bottom: 12px solid #00A651;
    border-left: 24px solid #00A651;
    border-right: 24px solid #00A651;
    border-top: 12px solid #00A651;
}

.button-red,
.button-error {
    background-color: #dc2626;
    border-bottom: 12px solid #dc2626;
    border-left: 24px solid #dc2626;
    border-right: 24px solid #dc2626;
    border-top: 12px solid #dc2626;
}

.panel {
    border-left: #00A651 solid 4px;
    margin: 24px 0;
}

.panel-content {
    background-color: #eefbf4;
    color: #334155;
    padding: 16px;
}

.table table {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 24px auto;
    width: 100%;
}

.table th {
    border-bottom: 1px solid #e5e7eb;
    color: #111827;
    padding-bottom: 8px;
}

.table td {
    color: #4b5563;
    font-size: 14px;
    line-height: 18px;
    padding: 10px 0;
}

.subcopy {
    border-top: 1px solid #e5e7eb;
    margin-top: 28px;
    padding-top: 22px;
}

.subcopy p {
    color: #6b7280;
    font-size: 13px;
}

.footer {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 570px;
    margin: 0 auto;
    padding: 28px 0 34px;
    text-align: left;
    width: 570px;
}

.footer p {
    color: #9ca3af;
    font-size: 12px;
    line-height: 1.5;
    text-align: left;
}

.footer a {
    color: #6b7280;
}

.break-all {
    word-break: break-all;
}

@media only screen and (max-width: 600px) {
    .inner-body,
    .footer {
        width: calc(100% - 24px) !important;
    }

    .content-cell {
        padding: 30px 28px 28px !important;
    }

    .mail-brand-header {
        padding: 20px 28px !important;
    }

    .mail-brand-logo {
        max-height: 38px !important;
        max-width: 154px !important;
    }

    h1 {
        font-size: 23px !important;
    }
}

@media only screen and (max-width: 420px) {
    .button {
        display: block !important;
        text-align: center !important;
        width: auto !important;
    }
}
