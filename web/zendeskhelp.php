<?php
$url = FALSE;

//print_r($_GET);

if(isset($_GET['ajaxsearch'])) {
	
	//this is ajax search, requires url rewrites in .htaccess on nginx conf
	if($_GET['ajaxsearch']==1) {
		//https://help.friday-ad.co.uk/hc/api/internal/instant_search.json?query=stuffff&locale=en-us
		echo file_get_contents("https://help.friday-ad.co.uk/hc/api/internal/instant_search.json?query=".urlencode($_GET['query'])."&locale=en-us");
		//just output json responce
		exit();
	} elseif($_GET['ajaxsearch']==2) {
		//https://help.friday-ad.co.uk/hc/search/instant_click?data=BAh7CjoHaWRsKwgdTf/RUwA6CXR5cGVJIgxhcnRpY2xlBjoGRVQ6CHVybEkiQS9oYy9lbi11cy9hcnRpY2xlcy8zNjAwMDU0NTUxMzMtV2hhdC1pcy15b3VyLVJldHVybnMtUG9saWN5LQY7B1Q6DnNlYXJjaF9pZEkiKTg4OWQ5MTZkLWJkMzgtNDhmMy05YmVhLWRkOWM3ZTY1MmJiOAY7B0Y6CXJhbmtpBg==--15a717a790d064c672514c0548612eada57d1a9c
		//this redirects to search result page, need to pass on for processing links
		//echo file_get_contents("https://help.friday-ad.co.uk/hc/search/instant_click?data=".urlencode($_GET['data'])."");
		$url = "https://help.friday-ad.co.uk/hc/search/instant_click?data=".urlencode($_GET['data']);
	}

} else {

	//this is the regular processing
	if(!isset($_GET['url'])) {

		if(isset($_GET['query'])) {
		
			if(isset($_GET['page'])) {
				$url = "https://help.friday-ad.co.uk/hc/en-us/search?query=".urlencode($_GET['query'])."&page=".urlencode($_GET['page']);
			} else {
				$url = "https://help.friday-ad.co.uk/hc/en-us/search?query=".urlencode($_GET['query']);
			}

		} else {
			?>
			Sorry, there was an error loading our Help Centre. Please visit it directly: <a href="https://help.friday-ad.co.uk" targer="_blank">https://help.friday-ad.co.uk</a>
			<?php
			exit("<br/><br/>ns");
		}

	} else {
		$url = $_GET['url'];
	}
}
	if(strpos($url, "help.friday-ad.co.uk") === FALSE) {
		?>
		Sorry, there was an error loading our Help Centre. Please visit it directly: <a href="https://help.friday-ad.co.uk" targer="_blank">https://help.friday-ad.co.uk</a>
		<?php
		exit();
	}

	if(strpos($url, "search/click?data=") !== FALSE) {
		//special case for search results when sometimes hashed thing has chars like +
		$bits = explode("?data=", $url);
		$url = $bits[0]."?data=".urlencode($bits[1]);
	}

	$a = file_get_contents($url);

	//wrap all a href with widget (this) file
	$pattern = '/\<a(.*?)href="(.+?)"(.*?)\>/i';
	$replacement = '<a $1 href="/zendeskhelp.php?url=$2" $3 >';
	$a = preg_replace($pattern, $replacement, $a);

	//fix all links to have a full url
	$pattern = '/=\/hc\/en-us/i';
	$replacement = '=https://help.friday-ad.co.uk/hc/en-us';
	$a = preg_replace($pattern, $replacement, $a);

	//voting is a no go. Cheat prevention makes it impossible to force into voting.
	//$a = str_replace("data-vote-url=\"/hc/en-us/", "data-vote-url=\"https://help.friday-ad.co.uk/hc/en-us/", $a);

	//bypass search
	$a = str_replace("action=\"/hc/en-us/search\"", "action=\"/zendeskhelp.php\"", $a);

	//fix false positive
	$a = str_replace("/zendeskhelp.php?url=#", "#", $a);

	//fix direct links to help.friday-ad site
	$a = str_replace("href=\"/zendeskhelp.php?url=https://www.help.friday-ad.co.uk", "target=\"_blank\" href=\"https://www.help.friday-ad.co.uk", $a);

	//fix direct links to Paypal site
	$a = str_replace("href=\"/zendeskhelp.php?url=https://www.paypal.com", "target=\"_blank\" href=\"https://www.paypal.com", $a);

	
	
	//fix inline images hosted on zendesk
	$a = str_replace("/hc/article_attachments", "https://help.friday-ad.co.uk/hc/article_attachments", $a);

	//disabling tracker url to fix error in console. Tracking xhr doesn't work because of cross origin headers.
	$a = str_replace("Tracker.track(", "//Tracker.track(", $a);

	//fix search pagination links
	$a = str_replace("?url=https://help.friday-ad.co.uk/hc/en-us/search?page=", "?page=", $a);
	//fix direct links to other sites
	$a = str_replace("href=\"/zendeskhelp.php?url=http://fadvertise.friday-ad.co.uk", "target=\"_blank\" href=\"http://fadvertise.friday-ad.co.uk", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://www.getsafeonline.org/", "target=\"_blank\" href=\"http://www.getsafeonline.org/", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=https://landlords.org.uk/tenants/your-landlord-nla-member", "target=\"_blank\" href=\"https://landlords.org.uk/tenants/your-landlord-nla-member", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://abta.com/", "target=\"_blank\" href=\"http://abta.com/", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=https://www.gov.uk/get-vehicle-information-from-dvla", "target=\"_blank\" href=\"https://www.gov.uk/get-vehicle-information-from-dvla", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://www.theaa.com/vehicle-check", "target=\"_blank\" href=\"http://www.theaa.com/vehicle-check", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=https://www.immobilise.com", "target=\"_blank\" href=\"https://www.immobilise.com", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=https://www.bikeregister.com", "target=\"_blank\" href=\"https://www.bikeregister.com", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=https://www.police.uk/contact/force-websites/", "target=\"_blank\" href=\"https://www.police.uk/contact/force-websites/", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://www.actionfraud.police.uk/", "target=\"_blank\" href=\"http://www.actionfraud.police.uk/", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://www.police.uk/", "target=\"_blank\" href=\"http://www.police.uk/", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=https://www.gov.uk/government/organisations/national-fraud-authority/about", "target=\"_blank\" href=\"https://www.gov.uk/government/organisations/national-fraud-authority/about", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://fridaymediagroup.com/careers/", "target=\"_blank\" href=\"http://fridaymediagroup.com/careers/", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://paag.org.uk", "target=\"_blank\" href=\"http://paag.org.uk", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=https://www.gov.uk/guidance/buying-a-cat-or-dog", "target=\"_blank\" href=\"https://www.gov.uk/guidance/buying-a-cat-or-dog", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=https://www.friday-ad.co.uk/paa/first_step", "target=\"_blank\" href=\"https://www.friday-ad.co.uk/paa/first_step", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://www.homeoffice.gov.uk/agencies-public-bodies/nfa/", "target=\"_blank\" href=\"http://www.homeoffice.gov.uk/agencies-public-bodies/nfa/", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://www.landlords.org.uk/tenants/your-landlord-nla-member", "target=\"_blank\" href=\"http://www.landlords.org.uk/tenants/your-landlord-nla-member", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://www.theaa.com/car-data-checks/", "target=\"_blank\" href=\"http://www.theaa.com/car-data-checks/", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=https://www.friday-ad.co.uk/login/", "target=\"_blank\" href=\"https://www.friday-ad.co.uk/login/", $a);
	$a = str_replace("href=\"/zendeskhelp.php?url=http://www.friday-ad.co.uk/paa/first_step", "target=\"_blank\" href=\"http://www.friday-ad.co.uk/paa/first_step", $a);
	
	echo $a;
	
if(isset($_GET['query'])) {
?>

<script type="text/javascript">
$(document).ready(function() { 
	var searchContent = $(".page-header-description").html();
	var searchCount = parseInt(searchContent);
	var searchText = $("input[name='query']").val();
	if(isNaN(searchCount)) {
		searchCount = 0;
	}
	ga('send', 'event', 'help widget', 'search', "'"+searchText+" | "+searchCount+"'");
});
</script>
<?php } ?>