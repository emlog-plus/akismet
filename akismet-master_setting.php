<?php

/*
 * Akismet 后台界面
 * Author: vibbow
 * Date: 2013/07/07
 */
!defined('EMLOG_ROOT') && exit('access deined!');

function plugin_setting_view() {
	$apikey = akismet_get_config('apikey');

	echo <<<EOF
<script>
$("#menu_mg").addClass('active');
$("#akismet").addClass('active-page');
setTimeout(hideActived,2600);
</script>
<div class="heading-bg  card-views">
<ul class="breadcrumbs">
<li><a href="./"><i class="fa fa-home"></i> 首页</a></li>
<li class="active">Akismet 反垃圾评论</li>
</ul>
</div>
EOF;

	if (isset($_GET['setting']))
		echo '<div class="actived alert alert-success alert-dismissable">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>插件设置完成</div>';
	else if (isset($_GET['error']))
		echo '<div class="actived alert alert-success alert-dismissable">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>内部错误</div>';

	if (!extension_loaded('curl'))
		$message = '对不起，您的主机未启用 php-curl 扩展，请联系主机商解决。';
	else if (empty($apikey))
		$message = '请配置此插件的API Key，以使你的反垃圾策略生效。';
	else {
		$check = akismet_check_key($apikey);
		if ($check == 'valid')
			$message = '您已成功连接到了Akismet反垃圾评论服务器。';
		else if ($check == 'invalid')
			$message = '您使用的API Key无效，请检查设置。';
		else
			$message = '连接到Akismet服务器失败，请检查服务器设置。';
	}

	echo <<< EOF
<div class="row">
<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
<div class="panel panel-primary card-view">
<div class="panel-heading">
<div class="pull-left">
<h6 class="panel-title txt-light">温馨提示</h6>
</div>
<div class="clearfix"></div>
</div>
<div class="panel-wrapper collapse in">
<div class="panel-body">
<p>{$message}<br />
相关Key申请： <a href="https://akismet.com/signup/" target="_black">免费申请</a></p>
</div>
</div>
</div>
</div>
</div>

EOF;

	$block_ip_check = akismet_get_config('blockip') ? ' checked="checked"' : '';
	$check_url_check = akismet_get_config('checkurl') ? ' checked="checked"' : '';
	$del_comment_check = akismet_get_config('delcomment') ? ' checked="checked"' : '';
	$use_cdn_check = akismet_get_config('usecdn') ? ' checked="checked"' : '';

	$block_ip_count = substr_count(akismet_get_config('blocklist'), '|') / 2;
	$block_count = akismet_get_config('blockcount');
	$spam_count = akismet_get_config('spamcount');
	$url_count = akismet_get_config('urlcount');

	echo <<< EOF
<form action="plugin.php?plugin=akismet-master&action=setting" method="post">
<div class="row">
<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
<div class="panel panel-primary card-view">
<div class="form-group">
<label class="control-label mb-10">
Akismet API Key: </label>
<input size="30" name="akismet_key" class="form-control" type="text" value="{$apikey}"/>
</div>
<div class="checkbox checkbox-success">
<input type="checkbox" value="TRUE" name="akismet_check_url"{$check_url_check}/><label class="control-label mb-10">如果评论中含有 &quot;&lt;a href=&quot; 或 &quot;[url=&quot; 或 &quot;http://&quot; 则自动将其标为待审核状态</label>
</div>
<div class="checkbox checkbox-primary ">
<input type="checkbox" value="TRUE" name="akismet_blockip"{$block_ip_check}/>
<label class="control-label mb-10">屏蔽多次尝试发表垃圾评论的IP</label>
</div>
<div class="checkbox checkbox-danger">
<input type="checkbox" value="TRUE" name="akismet_del_comment"{$del_comment_check}/>
<label class="control-label mb-10">遇到垃圾评论直接删除</label>
</div>
<div class="checkbox checkbox-info ">
<input type="checkbox" value="TRUE" name="akismet_usecdn"{$use_cdn_check}/>
<label class="control-label mb-10">我的博客使用了CDN</label>
</div>
</div>
</div>
</div>
<div class="row">
<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
<div class="panel panel-primary card-view">
<p><b>统计信息：</b></p>
<p>被屏蔽的IP数: {$block_ip_count}</p>
<p>通过屏蔽IP拦截的评论数: {$block_count}</p>
<p>通过Akismet拦截的评论数: {$spam_count}</p>
<p>通过网址前缀匹配隐藏的评论数: {$url_count}</p>
<br />
<div class="clearfix"></div>
<div class="form-group text-center">	
<input type="submit" name="akismet_save" value="保存设置" class="btn btn-success" />
&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="akismet_clean" value="清理所有未审核评论" class="btn btn-info" />
</div>
</div>
</div>
</div>
</div>
</form>

EOF;
}

function akismet_check_key($apikey) {
	$url = 'http://rest.akismet.com/1.1/verify-key';
	$post = 'key=' . urlencode($apikey) . '&blog=' . urlencode(BLOG_URL);
	return akismet_remote($url, $post);
}

function plugin_setting() {
	if (isset($_POST['akismet_save'])) {
		$apikey = isset($_POST['akismet_key']) ? trim($_POST['akismet_key']) : '';
		$check_url = isset($_POST['akismet_check_url']) ? $_POST['akismet_check_url'] : 'FALSE';
		$del_comment = isset($_POST['akismet_del_comment']) ? $_POST['akismet_del_comment'] : 'FALSE';
		$block_ip = isset($_POST['akismet_blockip']) ? $_POST['akismet_blockip'] : 'FALSE';
		$use_cdn = isset($_POST['akismet_usecdn']) ? $_POST['akismet_usecdn'] : 'FALSE';

		if (empty($apikey) || akismet_check_key($apikey) != 'valid')
			emMsg('检测API Key有效性时失败，请检查API Key或服务器设置', BLOG_URL . 'admin/plugin.php?plugin=akismet-master');

		if ($check_url != 'TRUE')
			$check_url = 'FALSE';

		if ($del_comment != 'TRUE')
			$del_comment = 'FALSE';

		if ($block_ip != 'TRUE')
			$block_ip = 'FALSE';

		if ($use_cdn != 'TRUE')
			$use_cdn = 'FALSE';

		akismet_set_config('apikey', $apikey);
		akismet_set_config('checkurl', $check_url);
		akismet_set_config('delcomment', $del_comment);
		akismet_set_config('blockip', $block_ip);
		akismet_set_config('usecdn', $use_cdn);
		akismet_update_cache();
	}

	if (isset($_POST['akismet_clean'])) {
		$db = Database::getInstance();
		$sql = "DELETE FROM " . DB_PREFIX . "comment WHERE hide = 'y'";
		$db->query($sql);

		$cache = Cache::getInstance();
		$cache->updateCache(array('sta', 'comment'));
	}
}

?>