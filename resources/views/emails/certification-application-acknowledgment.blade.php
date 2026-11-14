@php
    $appName = config('app.name');
    $logoUrl = asset('images/brand-logo.png');
    $homeUrl = route('home');
    $productsUrl = route('storefront.products');
    $companiesUrl = route('storefront.companies');
@endphp
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
<title>{{ __('Application received for Halal Certification') }} &mdash; {{ $appName }}</title>
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
td,th,div,p,a,h1 {font-family:Arial, sans-serif; mso-line-height-rule:exactly;}
</style>
<![endif]-->
<style>
  body, table, td { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
  table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
  img { border:0; line-height:100%; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
  a { text-decoration:none; }
  @media only screen and (max-width:600px) {
    .email-wrapper { width:100% !important; }
    .fluid { width:100% !important; max-width:100% !important; }
    .stack-padding { padding-left:24px !important; padding-right:24px !important; }
    .h1-mobile { font-size:20px !important; }
  }
</style>
</head>
<body style="margin:0; padding:0; background-color:#F0EEE9; word-spacing:normal;">
<!-- Preheader -->
<div style="display:none; max-height:0; max-width:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#F0EEE9;">
{{ __('We have received your Letter of Intent for Halal Certification.') }}
 &zwnj; &zwnj; &zwnj; &zwnj; &zwnj; &zwnj; &zwnj; &zwnj; &zwnj; &zwnj;
</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F0EEE9;">
<tr>
<td align="center" style="padding:32px 16px;">
<!--[if mso]>
<table role="presentation" align="center" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td>
<![endif]-->
<table role="presentation" class="email-wrapper" align="center" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#FFFFFF; border-radius:10px; overflow:hidden;">
  <!-- Header -->
  <tr>
    <td align="center" bgcolor="#00312B" style="background-color:#00312B; padding:28px 24px 24px;">
      <img src="{{ $logoUrl }}" width="150" height="41" alt="{{ $appName }}" style="display:block; width:150px; height:auto; max-width:150px;" />
    </td>
  </tr>
  <tr>
    <td style="line-height:0; font-size:0;"><div style="height:4px; line-height:4px; background-color:#EEC900;">&nbsp;</div></td>
  </tr>
  <!-- Body -->
  <tr>
    <td class="stack-padding" style="padding:40px 40px 32px;">
      <h1 class="h1-mobile" style="margin:0 0 16px; font-family:Arial, Helvetica, sans-serif; font-size:22px; line-height:28px; font-weight:bold; color:#00312B; text-align:left;">
        {{ __('Application received for Halal Certification') }}
      </h1>
      <p style="margin:0 0 16px; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:24px; color:#333333; text-align:left;">
        {{ __('Dear') }} <strong>{{ $application->company_name }}</strong>,
      </p>
      <p style="margin:0 0 16px; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:24px; color:#333333; text-align:left;">
        {{ __('We have received your Letter of Intent for Halal Certification. Our team will review the document and contact you at') }} <strong>{{ $application->email }}</strong> {{ __('with the next steps.') }}
      </p>
      <p style="margin:0 0 32px; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:24px; color:#333333; text-align:left;">
        {{ __('If you have any questions in the meantime, feel free to reply to this email.') }}
      </p>
      <p style="margin:0; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:24px; color:#333333; text-align:left;">
        {{ __('Regards,') }}<br />{{ __('The :appName Team', ['appName' => $appName]) }}
      </p>
    </td>
  </tr>
  <!-- Footer -->
  <tr>
    <td align="center" bgcolor="#FAF9F6" style="background-color:#FAF9F6; padding:32px 40px; border-top:1px solid #EEEEEE;">
      <p style="margin:0 0 12px; font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:20px; color:#666666; text-align:center;">
        {{ __('From Farm to Plate') }} &mdash; {{ __('Bringing Halal Products, Services and Opportunities to the Marketplace.') }}
      </p>
      <p style="margin:0 0 16px; font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:20px; text-align:center;">
        <a href="{{ $homeUrl }}" style="color:#00312B; text-decoration:underline;">{{ __('Home') }}</a>
         &middot;
        <a href="{{ $productsUrl }}" style="color:#00312B; text-decoration:underline;">{{ __('Products') }}</a>
         &middot;
        <a href="{{ $companiesUrl }}" style="color:#00312B; text-decoration:underline;">{{ __('Verified Businesses') }}</a>
      </p>
      <p style="margin:0; font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:18px; color:#999999; text-align:center;">
        &copy; {{ date('Y') }} {{ $appName }}. {{ __('All rights reserved.') }}<br />
        {{ __('Owned and operated by Maslaha Halal Certification Board Inc.') }}
      </p>
    </td>
  </tr>
</table>
<!--[if mso]>
</td></tr></table>
<![endif]-->
</td>
</tr>
</table>
</body>
</html>
