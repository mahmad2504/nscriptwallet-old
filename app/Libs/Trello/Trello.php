<?php

namespace App\Libs\Trello;

class Trello
{
	private $key=null;
	private $token=null;
	public function __construct($key=null,$token=null)
	{
		$this->key = $key==null? env("TRELLO_KEY"):$key;
		$this->token = $token==null? env("TRELLO_TOKEN"):$token;
	}
	public function GetResource($resource,$fields)
	{
		$url="https://api.trello.com/1".$resource."?key=".$this->key."&token=".$this->token."&fields=".$fields;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		$data = curl_exec($ch);
		curl_close($ch);
		return json_decode($data);
	}
	public function GetActions($ticketid,$filter='updateCheckItemStateOnCard')
	{
		$url = "https://api.trello.com/1/cards/".$ticketid."/actions?key=".$this->key.'&token='.$this->token."&filter=".$filter;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		$actions = curl_exec($ch);
		curl_close($ch);
		return json_decode($actions);
	}
	public function Boards($fields="name,id,dateLastActivity")
	{
		return $this->GetResource("/members/me/boards",$fields);	
	}
	public function Board($boardid,$fields="name,dateLastActivity")
	{
		return $this->GetResource("/boards/".$boardid,$fields);
	}
	public function Lists($boardid,$fields="name,id")
	{
		return $this->GetResource("/boards/".$boardid."/lists",$fields);
	}
	public function ListClosedCardsOnBoard($boardid,$fields="dateLastActivity,idList,closed&filter=closed")
	{
		return $this->GetResource("/board/".$boardid."/cards",$fields);
	}
	public function ListCardsOnBoard($boardid,$fields="dateLastActivity,idList,closed")
	{
		return $this->GetResource("/board/".$boardid."/cards",$fields);
	}
	public function ListCards($listid,$fields="dateLastActivity,closed")
	{
		return $this->GetResource("/lists/".$listid."/cards",$fields);
	}
	public function Card($cardid,$fields='name,badges,desc,labels,url,dueComplete,idChecklists,idList')
	{
		return $this->GetResource("/cards/".$cardid,$fields);
	}
	public function Attachment($cardid,$fields='name')
	{
		return $this->GetResource("/cards/".$cardid."/attachments",'name');
	}
	public function Checklist($id,$fields='checkItems')
	{
		return $this->GetResource("/checklists/".$id,$fields);
}	}