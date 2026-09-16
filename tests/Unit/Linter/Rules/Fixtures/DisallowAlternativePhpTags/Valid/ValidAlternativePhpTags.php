<?php

$scriptTag = '<script language="php">';
$aspTags = '<%= $value %> and <% echo $value; %>';
// <script language=php><%= $value %><% echo $value; %>
?>
<?= $scriptTag ?>
<? echo 'Handled by no-short-opening-tag.'; ?>
Plain inline content with standalone </script> and %> closing markers.
