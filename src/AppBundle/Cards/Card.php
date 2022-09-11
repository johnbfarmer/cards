<?php

namespace AppBundle\Cards;

class Card  extends BaseProcess{
	protected $suit;
	protected $value;
	protected $sortOrder;
	protected $idx;
	protected static $suits = ['♣', '♦', '♥', '♠'];
	protected static $faces = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];

	public function __construct($idx)
	{
		$this->idx = $idx;
		$this->suit = $idx % 4;
		$this->value = floor($idx / 4);
		$this->sortOrder = 13 * $this->suit + $this->value;
	}

	public function display()
	{
		return self::$faces[$this->value] . self::$suits[$this->suit];
	}

	public function getSuit()
	{
		return $this->suit;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function getSortOrder()
	{
		return $this->sortOrder;
	}

	public static function getDisplayFromIdx($idx)
	{
		return self::$faces[floor($idx / 4)] . self::$suits[$idx % 4];
	}
}
