<style>
.tpl-e{ padding: 24px 48px;font-family: '微软雅黑'; color: #333; font-size: 16px;}
.tpl-e h1{ font-size: 80px; font-weight: normal; line-height: 120px; margin-bottom: 12px;float: left;margin: 0 40px 0 0;}
.tpl-e .jump{ padding-top: 10px;margin: 10px 0;}
.tpl-e .jump a{ color: #333;text-decoration: underline;}
.tpl-e .success,.tpl-e .error{ line-height: 1.8em; font-size: 24px;margin-top: 25px;min-height: 43px; margin-bottom: 10px;}
</style>

<div class="tpl-e">
<?php if( $message ){ ?>
	<h1>:)</h1>
	<p class="success"><?php echo $message; ?></p>
<?php }else{ ?>
	<h1>:(</h1>
	<p class="error"><?php echo $error; ?></p>
<?php } ?>
	<p class="jump">
		页面自动 <a id="href" href="<?php echo $jumpUrl; ?>">跳转</a>
		等待时间： <b id="wait"><?php echo $waitSecond; ?></b>
	</p>
</div>

<script type="text/javascript">
	(function(){
	var wait = document.getElementById('wait')
	var href = document.getElementById('href').href;
	var interval = setInterval(function(){
		var time = --wait.innerHTML;
		if(time <= 0) {
			location.href = href;
			clearInterval(interval);
		};
	}, 1000);
	})();
</script>
