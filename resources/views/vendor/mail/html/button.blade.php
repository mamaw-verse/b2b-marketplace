@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php
    $fillColor = $color === 'error' || $color === 'red' ? '#A11313' : '#00312B';
    $textColor = $color === 'success' || $color === 'green' ? '#FFFFFF' : ($color === 'error' || $color === 'red' ? '#FFFFFF' : '#EEC900');
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table class="cta-table" align="{{ $align }}" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center" bgcolor="{{ $fillColor }}" style="border-radius:6px;">
<!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:50px;v-text-anchor:middle;width:280px;" arcsize="12%" stroke="f" fillcolor="{{ $fillColor }}">
<w:anchorlock/>
<center style="color:{{ $textColor }};font-family:Arial,sans-serif;font-size:16px;font-weight:bold;">{{ $slot }}</center>
</v:roundrect>
<![endif]-->
<!--[if !mso]><!-->
<a href="{{ $url }}" target="_blank" rel="noopener" class="button button-{{ $color }} cta-link" style="display:inline-block; width:280px; padding:15px 0; font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold; color:{{ $textColor }}; text-align:center; text-decoration:none; mso-hide:all;">{!! $slot !!}</a>
<!--<![endif]-->
</td>
</tr>
</table>
</td>
</tr>
</table>
