<style>
.tpl-e{ padding: 24px 48px;font-family: '微软雅黑'; color: #333; font-size: 16px;}
.tpl-e h1{ font-size: 80px; font-weight: normal; line-height: 120px; margin-bottom: 12px;float: left;margin: 0 40px 0 0;}
.tpl-e .jump{ padding-top: 10px;margin: 10px 0;}
.tpl-e .jump a{ color: #333;text-decoration: underline;}
.tpl-e .success,.tpl-e .error{ line-height: 1.8em; font-size: 24px;margin-top: 25px;min-height: 43px; margin-bottom: 10px;}
.tpl-e .detail{ clear: both;}
</style>

<div class="tpl-e">
	<h1>:(</h1>
	<p class="error"><?php echo $e['message']; ?></p>
	<p class="jump">
		<?php echo "File: {$e['file']} (Line: {$e['line']})"; ?>
	</p>
	<div class="detail"> <?php echo nl2br($e['trace']);?> </div>
</div>
