@props(['url'])
<tr>
<td class="header" align="center" bgcolor="#00312B">
<a href="{{ $url }}" style="display:inline-block; text-decoration:none;">
<img src="{{ asset('images/brand-logo.png') }}" width="150" height="41" class="logo" alt="{{ $slot }}" style="display:block; width:150px; height:auto; max-width:150px;">
</a>
</td>
</tr>
<tr>
<td class="header-bar" style="line-height:0; font-size:0;"><div style="height:4px; line-height:4px; background-color:#EEC900;">&nbsp;</div></td>
</tr>
