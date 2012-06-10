<?php

function trans($str) {
	global $language, $tr;
	if (!array_key_exists($language,$tr)) echo "<!-- no $language -->";
	if ($trans = @$tr[$language][$str]) return $trans;
	else return $str;
}

$lang = array(
	'en' => 'English',
	'zh' => '中国的',
	'fr' => 'French',
);
	
$tr = array( 

   'zh' => array(
	"Browse zones"=>"浏览区",
        "Browse records"=>"浏览记录",
        "Add a zone"=>"添加区域",
        "Delete a zone"=>"删除区域",
        "Misc. tools"=>"杂项”工具",
        "Push updates"=>"推送更新",
        "Logout"=>"登出",
	"Zone"=>"区",
        "Domain"=>"域",
        "Domain name"=>"域名",
        "Owner"=>"所有者",
	"Host"=>"主机",
        "Type"=>"类型",
        "Pref"=>"首选项",
        "Data"=>"域名",
        "Search"=>"搜索",
	"For"=>"于",
        "Master"=>"大师",
        "Master server"=>"主服务器",
	"Master zones"=>"大师区",
	"Slave zones"=>"奴隶区",
	"All zones"=>"凡区",
	"Annotations"=>"主机",
        "Slave"=>"奴隶",
        "First"=>"第一",
        "Next"=>"下一步",
        "Prev"=>"上一页",
        "Last"=>"最后",
        "Help"=>"帮助",
        "Pushing DNS updates to the servers"=>"推DNS更新服务器",
	"Server"=>"服务器",
	"Yes"=>"是的",
	"No"=>"没有",
	"N/A"=>"不适用",
	"No such domain"=>"没有这样的域名",
	"Enter one or more names of domains to add to the database, each on a separate line"=>"一个单独的行上输入一个或多个名称的域添加到数据库中，每个",
	),

   'fr' => array(
	"Browse zones"=>"Naviguez zones",
	"Browse records"=>"Registres parcourir",
	"Add a zone"=>"Ajouter une zone",
	"Delete a zone"=>"Supprimer une zone",
	"Misc. tools"=>"Misc. Outils",
	"Push updates"=>"Poussez les mises à jour",
	"Logout"=>"Déconnexion",
	),

);

?>
