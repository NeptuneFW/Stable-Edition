<?php

namespace Sup;

class Regex
{
	protected $expression;
	protected $openDelimiters;
	protected $flags;

	public function __construct()
  {
		$this->reset();
	}
	public function reset()
	{
		$this->expression = '';
		$this->openDelimiters = 0;
		$this->flags = '';

		return $this;
	}
	public function get($delimit = true)
	{
		if ($this->openDelimiters !== 0) {
			die('A delimiter has not been closed.');
		}

		$expression = $this->expression;

    if ($delimit) {
			$expression = '/'.$expression.'/'.$this->flags;
		}

		return $expression;
	}
	public function __toString()
	{
		return $this->get();
	}
	public function value($value)
	{
		$this->expression .= $value;

		return $this;
	}
	public function startGroupCapture()
	{
		$this->expression .= '(';
		$this->openDelimiters++;

		return $this;
	}
	public function endGroupCapture()
	{
		$this->value(')');
		$this->openDelimiters--;

		return $this;
	}
	public function startRange()
	{
		$this->value('[');
		$this->openDelimiters++;

		return $this;
	}
	public function endRange()
	{
		$this->value(']');
		$this->openDelimiters--;

		return $this;
	}
	public function lowercase()
	{
		$this->value('a-z');

		return $this;
	}
	public function uppercase()
	{
		$this->value('A-Z');

		return $this;
	}
	public function numeric()
	{
		$this->value('0-9');

		return $this;
	}
	public function any()
	{
		$this->value('.');

		return $this;
	}
	public function start()
	{
		$this->value('^');

		return $this;
	}
	public function end()
	{
		$this->value('$');

		return $this;
	}
	public function noneOrOne()
	{
		$this->value('?');

		return $this;
	}
	public function noneOrMany()
	{
		$this->value('*');

		return $this;
	}
	public function oneOrMore()
	{
		$this->value('+');

		return $this;
	}
	public function addOr()
	{
		$this->value('|');

		return $this;
	}
	public function matchQuantity($min, $max = null)
	{
		$match = '{'.$min;

		if ($max !== null) {
			$match .= ',' . $max;
		}

		$match .= '}';
		$this->value($match);

		return $this;
	}
	public function caseInsensitive()
	{
		$this->flags .= 'i';

		return $this;
	}
	public function ignoreWhitespace()
	{
		$this->flags .= 'x';

		return $this;
	}
	public function singleSubstitution()
	{
		$this->flags .= 'o';

		return $this;
	}
	public function dotNewline()
	{
		$this->flags .= 'm';
		return $this;
	}
}
