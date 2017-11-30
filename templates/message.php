<?php
\OCP\Util::addStyle('registration', 'style');
?>
<ul class="msg error-wide">
	<li><?php print_unescaped($_['msg'])?></li>
</ul>
<?php print_unescaped($_['home']) ?>
