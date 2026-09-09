@php
    $appName = config('app.name');
    $firstName = $firstName ?? null;
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
<title>{{ __('Verify your email') }} &mdash; {{ $appName }}</title>
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
    .cta-table { width:100% !important; }
    .cta-link { width:100% !important; display:block !important; }
    .h1-mobile { font-size:20px !important; }
  }
</style>
</head>
<body style="margin:0; padding:0; background-color:#F0EEE9; word-spacing:normal;">
<!-- Preheader (hidden preview text) -->
<div style="display:none; max-height:0; max-width:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#F0EEE9;">
{{ __('Confirm your email to start sourcing verified Halal products on :appName.', ['appName' => $appName]) }}
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
    <td class="stack-padding" style="padding:40px 40px 8px;">
      <h1 class="h1-mobile" style="margin:0 0 16px; font-family:Arial, Helvetica, sans-serif; font-size:22px; line-height:28px; font-weight:bold; color:#00312B; text-align:left;">
        {{ __('Verify your email address') }}
      </h1>
      <p style="margin:0 0 16px; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:24px; color:#333333; text-align:left;">
        {{ __('Assalamu alaikum') }}{{ ($firstName ?? null) ? ', '.$firstName : '' }},
      </p>
      <p style="margin:0 0 24px; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:24px; color:#333333; text-align:left;">
        {{ __('Thanks for joining') }} <strong>{{ $appName }}</strong>{{ __(', the B2B marketplace connecting verified Halal suppliers and buyers.') }} {{ __('Please confirm your email address to activate your account and start requesting quotations.') }}
      </p>
    </td>
  </tr>
  <!-- CTA button (bulletproof, VML fallback for Outlook) -->
  <tr>
    <td align="center" style="padding:0 40px 28px;">
      <table role="presentation" class="cta-table" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td align="center" bgcolor="#00312B" style="border-radius:6px;">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:50px;v-text-anchor:middle;width:280px;" arcsize="12%" stroke="f" fillcolor="#00312B">
            <w:anchorlock/>
            <center style="color:#EEC900;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;">{{ __('Verify Email Address') }}</center>
            </v:roundrect>
            <![endif]-->
            <!--[if !mso]><!-->
            <a href="{{ $url }}" target="_blank" class="cta-link" style="display:inline-block; width:280px; padding:15px 0; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold; color:#EEC900; text-align:center; text-decoration:none; mso-hide:all;">
              {{ __('Verify Email Address') }}
            </a>
            <!--<![endif]-->
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <!-- Secondary copy -->
  <tr>
    <td class="stack-padding" style="padding:0 40px 32px;">
      <p style="margin:0 0 16px; font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:22px; color:#555555; text-align:left;">
        {{ __('This link will expire soon for your security. If you did not create an account with :appName, no further action is required', ['appName' => $appName]) }} &mdash; {{ __('you can safely ignore this email.') }}
      </p>
      <p style="margin:0; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:24px; color:#333333; text-align:left;">
        {{ __('Regards,') }}<br />{{ __('The :appName Team', ['appName' => $appName]) }}
      </p>
    </td>
  </tr>
  <!-- Divider -->
  <tr>
    <td style="padding:0 40px;">
      <div style="border-top:1px solid #E3E3E3; font-size:1px; line-height:1px;">&nbsp;</div>
    </td>
  </tr>
  <!-- Fallback link -->
  <tr>
    <td class="stack-padding" style="padding:24px 40px 40px;">
      <p style="margin:0; font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:20px; color:#777777; text-align:left;">
        {{ __('Trouble clicking the button? Copy and paste this URL into your browser:') }}<br />
        <a href="{{ $url }}" style="color:#00312B; text-decoration:underline; word-break:break-all;">{{ $url }}</a>
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
