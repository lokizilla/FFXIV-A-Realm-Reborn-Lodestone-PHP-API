<?php
/* Final Fantasy XIV: A Realm Reborn Lodestone API
* http://lokizilla.net
* Original version: Damian Miller (damian@offthewallmedia.com, http://rysas.net/ffxiv/)
* This version: Leslie Moore (loki@lokizilla.net, http://lokizilla.net/ffxiv)
* Updated: 08/09/2013
*/

include_once ('simple_html_dom.php');

class ffxivLodestoneAPI {
	// Version Number
	private static $ffxivLodestoneAPIVersion = '0.2 Alpha';

	// Singleton instance.
	private static $instance;

	// Final Fantasy Config Vars
	private $LodestoneURL = "http://eu.finalfantasyxiv.com/lodestone"; // EU/NA are identical for this purpose

	public $ServerList = array
	(
		2 => 'Cornelia',
		3 => 'Kashuan',
		4 => 'Gysahl',
		5 => 'Mysidia',
		6 => 'Istory',
		7 => 'Figaro',
		8 => 'Wutai',
		9 => 'Trabia',
		10 => 'Lindblum',
		11 => 'Besaid',
		12 => 'Selbina',
		13 => 'Rabanastre',
		14 => 'Bodhum',
		15 => 'Melmond',
		16 => 'Palamecia',
		17 => 'Saronia',
		18 => 'Fabul',
		19 => 'Karnak'
	);

	public $ClassList = array
	(
		2 => 'Hand-to-Hand',
		3 => 'Sword',
		4 => 'Axe',
		7 => 'Archery',
		8 => 'Polearm',
		22 => 'Thaumaturgy',
		23 => 'Conjury',
		29 => 'Woodworking',
		30 => 'Smithing',
		31 => 'Armorcraft',
		32 => 'Goldsmithing',
		33 => 'Leatherworking',
		34 => 'Clothcraft',
		35 => 'Alchemy',
		36 => 'Cooking',
		39 => 'Mining',
		40 => 'Botany',
		41 => 'Fishing',
	);  

	public static function GetInstance ( ) 
	{
		if ( !isset ( self::$instance ) ) {
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}

	public function GetHTMLObject($url) 
	{
		$context = array(
			'http' => array(
				'header' => 'Accept-Language: en-us,en;q=0.5\r\nAccept-Charset: utf-8;q=0.5\r\n',
				'user_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.08) Gecko/20100914 Firefox/3.6.10'
			)
		);

		$context = stream_context_create($context);

		// TODO:  check the php environment for use of the allow_url_fopen directive: if 1 then use this, if 0 then use cURL
		//        commented out for the time being as my development environment does not support the directive
		//return file_get_html( $this->LodestoneURL . $url, false, $context );

		// get the page using cURL
		$html = new simple_html_dom();
		$html = str_get_html($this->cURL($url));
		return $html;
	}

	private function cURL($url)
	{
		$cURL = curl_init($this->LodestoneURL.$url);

		curl_setopt($cURL, CURLOPT_HEADER, false); // return headers
		curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1); // output into a var
		curl_setopt($cURL, CURLOPT_TIMEOUT, 15); // timeout on response
		curl_setopt($cURL, CURLOPT_CONNECTTIMEOUT, 15); // timeout on connect
		curl_setopt($cURL, CURLOPT_FOLLOWLOCATION, false); // follow redirects
		curl_setopt($cURL, CURLOPT_MAXREDIRS, 5); // maximum redirects

		$output = curl_exec($cURL);
		curl_close($cURL);

		return $output;
	}

	public function SearchCharacterList($CharacterName, $Server = false, $Class = false)
	{
		$CharListObj = null;
		$Results = array();

		$html = $this->GetHTMLObject('/rc/search/search?tgt=77&q='.urlencode($CharacterName).(($Class)?'&cms='.$Class:false).(($Server)?'&cw='.$Server:false));

		// Find the character list... kind of blah but the DOM Library has limitations, so work around them!  
		$CharListObj = $html->find ('div.contents-frame table.contents-table1 tr td img.character-icon', 0)->parent()->parent()->parent()->parent()->parent()->parent()->removeNodes('tr',1);

		// Loop through each character in list

		foreach ($CharListObj->find('table tr') as $Char) 
		{
			$Result = new SimpleXMLElement("<Character></Character>");

			// Get Character ID and setup Results array.
			$CharID = $Char->find ('a[href^=/rc/character/top]', 0);
			$Result->CharName = $CharID->plaintext;
			$Result->CharacterID = substr ( $CharID->href, 25, strlen ($CharID->href) );

			// Start getting other data.
			$Result->CharacterImage = $Char->find ('img.character-icon', 0)->src;

			$Result->CharacterMainSkill = $Char->parent()->parent()->parent()->children(1)->plaintext;
			$Result->CharacterWorld = $Char->parent()->parent()->parent()->children(2)->plaintext;

			$Results[] = $Result;
		}

		return $Results;    
	}

	public function GetCharacterData($CharacterID) 
	{
		$Result = new SimpleXMLElement("<Character></Character>");

		$html = $this->GetHTMLObject ('/character/'.$CharacterID)->find('div#main', 0);

		$ProfileList = $html->find('ul.chara_profile_list', 0);
		$PowerGauge = $html->find('ul#power_gauge', 0);
		$AttributesTable = $html->find('ul.param_list_attributes', 0);
		$ElementsTable = $html->find('ul.param_list_elemental', 0);
		$SkillLevels = $html->find('div.base_inner', 0);

		$Result->CharacterName = $html->find('div.area_footer', 0)->children(2)->children(0)->plaintext;
		$Result->CharacterRace = substr($html->find('div.chara_profile_title', 0)->plaintext, 0, strpos($html->find('div.chara_profile_title', 0)->plaintext, "/") - 1);
		$Result->CharacterSubRace = substr($html->find('div.chara_profile_title', 0)->plaintext, strpos($html->find('div.chara_profile_title', 0)->plaintext, "/") + 2, (strpos(json_encode($html->find('div.chara_profile_title', 0)->plaintext), "\\n") - 2) - (strpos($html->find('div.chara_profile_title', 0)->plaintext, "/") + 2));

		if (strpos($html->find('div.chara_profile_title', 0), "♂") !== false) $Result->CharacterGender = 'Male'; else $Result->CharacterGender = 'Female';
		$Result->CharacterWorld = substr($html->find('div.area_footer', 0)->children(2)->children(1)->plaintext, 2, strpos($html->find('div.area_footer', 0)->children(2)->children(1)->plaintext, ")") - 2);
		$Result->CharacterImage264x360 = substr($html->find('div.bg_chara_264', 0)->find('img', 0), 10, strpos($html->find('div.bg_chara_264', 0)->find('img', 0), 'width') - 12);
		$Result->CharacterAvatar50x50 = substr($html->find('div.thumb_cont_black_40', 0)->find('img', 0), 10, strpos($html->find('div.thumb_cont_black_40', 0)->find('img', 0), 'width') - 12);
		$Result->CharacterNamesday = rtrim($ProfileList->children(0)->children(0)->children(0)->children(1)->children(0)->children(0)->children(1)->plaintext);
		$Result->CharacterGuardian = rtrim($ProfileList->children(0)->children(0)->children(0)->children(1)->children(0)->children(1)->children(1)->plaintext);
		$Result->CharacterStartingCity = rtrim($ProfileList->children(1)->children(2)->plaintext);

		if ($ProfileList->children(2)) // check for existence of grand / free company
		{
			if (strpos($ProfileList->children(2), "Free Company") !== false) // if free company:
			{
				$Result->CharacterFreeCompany = rtrim($ProfileList->children(2)->children(2)->plaintext);
				$Result->CharacterFreeCompanyURL = substr($ProfileList->children(2)->find('a', 0), 9, strpos($ProfileList->children(2)->find('a', 0), 'class') - 11);
			}
			else // if grand company:
			{
				$Result->CharacterGrandCompany = substr($ProfileList->children(2)->children(2)->plaintext, 0, strpos($ProfileList->children(2)->children(2)->plaintext, "/"));
				$Result->CharacterGrandCompanyRank = substr($ProfileList->children(2)->children(2)->plaintext, strpos($ProfileList->children(2)->children(2)->plaintext, "/") + 1);
				$Result->CharacterGrandCompanyRankIMG = substr($ProfileList->children(2)->find('img', 0), 10, strpos($ProfileList->children(2)->find('img', 0), 'width') - 12);
			}
		}

		if ($ProfileList->children(3)) // check for existence of free company (children(3); grand company is children(2))
		{
			$Result->CharacterFreeCompany = rtrim($ProfileList->children(3)->children(2)->plaintext);
			$Result->CharacterFreeCompanyURL = "http://eu.finalfantasyxiv.com/lodestone".substr($ProfileList->children(3)->find('a', 0), 9, strpos($ProfileList->children(3)->find('a', 0), 'class') - 11); // TODO: hax, fix
		}

		$Result->CharacterPowergauge->HP = rtrim($PowerGauge->children(0)->plaintext);
		$Result->CharacterPowergauge->MP = rtrim($PowerGauge->children(1)->plaintext); // TODO: name this as MP/CP
		$Result->CharacterPowergauge->TP = rtrim($PowerGauge->children(2)->plaintext);

		$Result->CharacterAttributes->Strength = rtrim($AttributesTable->children(0)->plaintext);
		$Result->CharacterAttributes->Vitality = rtrim($AttributesTable->children(1)->plaintext);
		$Result->CharacterAttributes->Dexterity = rtrim($AttributesTable->children(2)->plaintext);
		$Result->CharacterAttributes->Intelligence = rtrim($AttributesTable->children(3)->plaintext);
		$Result->CharacterAttributes->Mind = rtrim($AttributesTable->children(4)->plaintext);
		$Result->CharacterAttributes->Piety = rtrim($AttributesTable->children(5)->plaintext);

		$Result->CharacterElements->Fire = rtrim($ElementsTable->children(0)->children(0)->plaintext);
		$Result->CharacterElements->Water = rtrim($ElementsTable->children(1)->children(0)->plaintext);
		$Result->CharacterElements->Lightning = rtrim($ElementsTable->children(2)->children(0)->plaintext);
		$Result->CharacterElements->Wind = rtrim($ElementsTable->children(3)->children(0)->plaintext);
		$Result->CharacterElements->Earth = rtrim($ElementsTable->children(4)->children(0)->plaintext);
		$Result->CharacterElements->Ice = rtrim($ElementsTable->children(5)->children(0)->plaintext);

		// $Result->CharacterCurrentSkill = rtrim($ProfileTable->children(1)->children(1)->plaintext); // TODO: see below
		$Result->CharacterCurrentSkillLevel = substr(rtrim($html->find('div.level', 0)->plaintext), 6);

		$Result->CharacterSkillLevels->War->Gladiator->Level = $SkillLevels->children(1)->children(0)->children(0)->children(1)->plaintext;
		$Result->CharacterSkillLevels->War->Gladiator->EXP = $SkillLevels->children(1)->children(0)->children(0)->children(2)->plaintext;
		$Result->CharacterSkillLevels->War->Pugilist->Level = $SkillLevels->children(1)->children(0)->children(0)->children(4)->plaintext;
		$Result->CharacterSkillLevels->War->Pugilist->EXP = $SkillLevels->children(1)->children(0)->children(0)->children(5)->plaintext;
		$Result->CharacterSkillLevels->War->Marauder->Level = $SkillLevels->children(1)->children(0)->children(1)->children(1)->plaintext;
		$Result->CharacterSkillLevels->War->Marauder->EXP = $SkillLevels->children(1)->children(0)->children(1)->children(2)->plaintext;
		$Result->CharacterSkillLevels->War->Lancer->Level = $SkillLevels->children(1)->children(0)->children(1)->children(4)->plaintext;
		$Result->CharacterSkillLevels->War->Lancer->EXP = $SkillLevels->children(1)->children(0)->children(1)->children(5)->plaintext;
		$Result->CharacterSkillLevels->War->Archer->Level = $SkillLevels->children(1)->children(0)->children(2)->children(1)->plaintext;
		$Result->CharacterSkillLevels->War->Archer->EXP = $SkillLevels->children(1)->children(0)->children(2)->children(2)->plaintext;
		$Result->CharacterSkillLevels->Magic->Conjurer->Level = $SkillLevels->children(3)->children(0)->children(0)->children(1)->plaintext;
		$Result->CharacterSkillLevels->Magic->Conjurer->EXP = $SkillLevels->children(3)->children(0)->children(0)->children(2)->plaintext;
		$Result->CharacterSkillLevels->Magic->Thaumaturge->Level = $SkillLevels->children(3)->children(0)->children(0)->children(4)->plaintext;
		$Result->CharacterSkillLevels->Magic->Thaumaturge->EXP = $SkillLevels->children(3)->children(0)->children(0)->children(5)->plaintext;
		$Result->CharacterSkillLevels->Magic->Arcanist->Level = $SkillLevels->children(3)->children(0)->children(1)->children(1)->plaintext;
		$Result->CharacterSkillLevels->Magic->Arcanist->EXP = $SkillLevels->children(3)->children(0)->children(1)->children(2)->plaintext;
		$Result->CharacterSkillLevels->Hand->Carpenter->Level = $SkillLevels->children(5)->children(0)->children(0)->children(1)->plaintext;
		$Result->CharacterSkillLevels->Hand->Carpenter->EXP = $SkillLevels->children(5)->children(0)->children(0)->children(2)->plaintext;
		$Result->CharacterSkillLevels->Hand->Blacksmith->Level = $SkillLevels->children(5)->children(0)->children(0)->children(4)->plaintext;
		$Result->CharacterSkillLevels->Hand->Blacksmith->EXP = $SkillLevels->children(5)->children(0)->children(0)->children(5)->plaintext;
		$Result->CharacterSkillLevels->Hand->Armorer->Level = $SkillLevels->children(5)->children(0)->children(1)->children(1)->plaintext;
		$Result->CharacterSkillLevels->Hand->Armorer->EXP = $SkillLevels->children(5)->children(0)->children(1)->children(2)->plaintext;
		$Result->CharacterSkillLevels->Hand->Goldsmith->Level = $SkillLevels->children(5)->children(0)->children(1)->children(4)->plaintext;
		$Result->CharacterSkillLevels->Hand->Goldsmith->EXP = $SkillLevels->children(5)->children(0)->children(1)->children(5)->plaintext;
		$Result->CharacterSkillLevels->Hand->Leatherworker->Level = $SkillLevels->children(5)->children(0)->children(2)->children(1)->plaintext;
		$Result->CharacterSkillLevels->Hand->Leatherworker->EXP = $SkillLevels->children(5)->children(0)->children(2)->children(2)->plaintext;
		$Result->CharacterSkillLevels->Hand->Weaver->Level = $SkillLevels->children(5)->children(0)->children(2)->children(4)->plaintext;
		$Result->CharacterSkillLevels->Hand->Weaver->EXP = $SkillLevels->children(5)->children(0)->children(2)->children(5)->plaintext;
		$Result->CharacterSkillLevels->Hand->Alchemist->Level = $SkillLevels->children(5)->children(0)->children(3)->children(1)->plaintext;
		$Result->CharacterSkillLevels->Hand->Alchemist->EXP = $SkillLevels->children(5)->children(0)->children(3)->children(2)->plaintext;
		$Result->CharacterSkillLevels->Hand->Culinarian->Level = $SkillLevels->children(5)->children(0)->children(3)->children(4)->plaintext;
		$Result->CharacterSkillLevels->Hand->Culinarian->EXP = $SkillLevels->children(5)->children(0)->children(3)->children(5)->plaintext;
		$Result->CharacterSkillLevels->Land->Miner->Level = $SkillLevels->children(7)->children(0)->children(0)->children(1)->plaintext;
		$Result->CharacterSkillLevels->Land->Miner->EXP = $SkillLevels->children(7)->children(0)->children(0)->children(2)->plaintext;
		$Result->CharacterSkillLevels->Land->Botanist->Level = $SkillLevels->children(7)->children(0)->children(0)->children(4)->plaintext;
		$Result->CharacterSkillLevels->Land->Botanist->EXP = $SkillLevels->children(7)->children(0)->children(0)->children(5)->plaintext;
		$Result->CharacterSkillLevels->Land->Fisher->Level = $SkillLevels->children(7)->children(0)->children(1)->children(1)->plaintext;
		$Result->CharacterSkillLevels->Land->Fisher->EXP = $SkillLevels->children(7)->children(0)->children(1)->children(2)->plaintext;				

		return $Result;

		// TODO: currently equipped class
			// this is an image with stylised text, the source URL of which is obfuscated, and no alt or accompanying metadata
				// http://img.finalfantasyxiv.com/lds/pc/en/images/classname/e2a98c81ca279607fc1706e5e1b11bc08cac2578.png?1367039489 == thaumaturge
				// http://img.finalfantasyxiv.com/lds/pc/ja/images/classname/e2a98c81ca279607fc1706e5e1b11bc08cac2578.png?1367039489 == thaumaturge (JP)
			// it carries with it a small icon that is re-used in the CharacterSkillLevels section, where the filename/query are the same but the paths differ
				// http://img.finalfantasyxiv.com/lds/pc/global/images/class/24/e2a98c81ca279607fc1706e5e1b11bc08cac2578.png?1367039489 == thaumaturge
				// http://img.finalfantasyxiv.com/lds/pc/global/images/class/24/aab4391a4a5633684e1b93174713c1c52f791930.png?1367039489 == armorer
			// it should therefore be possible to iterate through these and eliminate each class until we find which one is suitable
			// this doesn't count for soul crystals, used in jobs; check the soul crystal first and take the name from the result
		// TODO: equipped items and stats in each slot
		// TODO: minions and mounts
		// TODO: character profile
		// TODO: validation
	}

	public function GetCharacterBiography($CharacterID) 
	{
		$Result = new SimpleXMLElement("<Character></Character>");

		$html = $this->GetHTMLObject ( '/rc/character/top?cicuid=' . $CharacterID )->find('div.floatRight table.contents-table1', 0)->removeNodes('tr',1);
		$Result->Biography = $html->find('td',0)->plaintext;  
		return $Result;
	}

	public function GetCharacterRecentBlogEntries ( $CharacterID ) 
	{
		$Results = array();

		$html = $this->GetHTMLObject ( '/rc/character/top?cicuid=' . $CharacterID )->find('div.floatRight table.contents-table1', 1)->removeNodes('tr',2);

		if( $html->find('td',0)->plaintext == "There are currently no entries to display.")

			return $Results;

		foreach ( $html->find('td') as $BlogPost )
		{
			$Result = new SimpleXMLElement("<BlogPost></BlogPost>");
			$Result->PostName = substr($BlogPost->find('a',0)->plaintext, 0, strpos($BlogPost->find('a',0)->plaintext, '&nbsp;'));
			$Result->PostCommentCount = (int) substr(substr($BlogPost->find('a',0)->plaintext,strpos($BlogPost->find('a',0)->plaintext, '&nbsp;')+7),0,-1);
			$Result->PostHref = $this->LodestoneURL . $BlogPost->find('a',0)->href;

			$Results[] = $Result;
		}

		return $Results;
	}


	public function GetCharacterFollowingCount($CharacterID)
	{
		$Result = new SimpleXMLElement("<Character></Character>");
		$Result->FollowingCount = (int) substr($this->GetHTMLObject ( '/rc/character/top?cicuid=' . $CharacterID )->find('div.ministatus-inner', 0)->find('tr',2)->plaintext,9); 
		return $Result;
	}


	public function GetCharacterFollowerCount($CharacterID)
	{
		$Result = new SimpleXMLElement("<Character></Character>");
		$Result->FollowerCount = (int) substr($this->GetHTMLObject ( '/rc/character/top?cicuid=' . $CharacterID )->find('div.ministatus-inner', 0)->find('tr',3)->plaintext,9); 
		return $Result;
	}

	public function GetCharacterHistory($CharacterID, $page = 1)
	{
		$Results = array();

		$html = $this->GetHTMLObject ( '/rc/character/playlog?num=100&cicuid=' . $CharacterID . '&p=' . $page )->find('div.community-inner div.contents-headline');

		if($page > 1)
			$i = $page * 100;
		else
			$i = 0;

		foreach ( $html as $History ) 
		{
			$Result = new SimpleXMLElement("<HistoryItem></HistoryItem>");
			$Result->Title = $History->plaintext;
			$Result->Type = substr($History->class,0, -18);      
			$Results[$i] = $Result;
			$i++;
		}

		$html = $this->GetHTMLObject ( '/rc/character/playlog?num=100&cicuid=' . $CharacterID . '&p=' . $page )->find('div.community-inner div.contents-frame');

		if($page > 1)
			$i = $page * 100;
		else
			$i = 0;

		foreach ( $html as $History ) 
		{
			$Results[$i]->Description = $History->children(0)->children(0)->children(0)->children(0)->plaintext;
			$Results[$i]->Time = $History->children(0)->children(0)->children(0)->children(1)->plaintext;
			$i++;
		}

		$html = $this->GetHTMLObject ( '/rc/character/playlog?num=100&cicuid=' . $CharacterID . '&p=' . $page )->find('td.common-pager-index');

		if($page <= 1) { 
			$count = count($html);
			if($count>1) {
				for($i = $page+1; $i <= $count; $i++) {
					array_merge($Results,$this->GetCharacterHistory ( $CharacterID, $i) );
				}
			}
		}
		return $Results;
	}
}

class ffxivLodestoneAPIError extends Exception
{
	public function apiError($resp) 
	{
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$xml .= "<Response status=\"error\"></Response>\n";
		return $xml;
	}
}

?>
