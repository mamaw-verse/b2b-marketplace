<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="color-scheme" content="light" />
<meta name="supported-color-schemes" content="light" />
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no" />
<meta name="x-apple-disable-message-reformatting" />
<title>{{ config('app.name') }}</title>
<!--[if mso]>
<noscript>
<xml>
<o:OfficeDocumentSettings>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml>
</noscript>
<style>
table {border-collapse:collapse;}
td,th,div,p,a,h1,h2,h3 {font-family:Arial, sans-serif; mso-line-height-rule:exactly;}
</style>
<![endif]-->
<style>
@media only screen and (max-width: 600px) {
.email-wrapper { width: 100% !important; }
.inner-body { width: 100% !important; }
.footer { width: 100% !important; }
.content-cell { padding-left: 24px !important; padding-right: 24px !important; }
.cta-table { width: 100% !important; }
.cta-link { width: 100% !important; display: block !important; }
}
</style>
{!! $head ?? '' !!}
</head>
<body style="margin:0; padding:0; background-color:#F0EEE9; word-spacing:normal;">

@isset($preheader)
<div style="display:none; max-height:0; max-width:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#F0EEE9;">
{{ $preheader }}
&zwnj;&zwnj;&zwnj;&zwnj;&zwnj;&zwnj;&zwnj;&zwnj;&zwnj;&zwnj;
</div>
@endisset

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#F0EEE9;">
<tr>
<td align="center" style="padding:32px 16px;">
<!--[if mso]>
<table role="presentation" align="center" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td>
<![endif]-->
<table class="content" width="600" cellpadding="0" cellspacing="0" role="presentation" style="width:600px; max-width:600px;">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0">
<table class="inner-body email-wrapper" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
<!--[if mso]>
</td></tr></table>
<![endif]-->
</td>
</tr>
</table>
</body>
</html>
