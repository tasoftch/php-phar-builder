<?php
/**
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace TASoft\Util\Code;


use ArrayAccess;

abstract class AbstractSourceCode implements ArrayAccess, SourceCodeInterface
{
	private $attributes = [];
	protected $contents;

	public function addAttribute($name, $value) {
		$this->attributes[$name] = $value;
	}

	public function hasAttribute($name): bool {
		return isset($this->attributes[$name]);
	}

	public function getAttribute($name) {
		return $this->attributes[$name] ?? NULL;
	}

	public function removeAtribute($name) {
		unset($this->attributes[$name]);
	}

	public function getAttributes(): array {
		return $this->attributes;
	}

	public function setAttributes(array $attributes) {
		$this->attributes = $attributes;
	}

	public function offsetExists($offset)
	{
		return $this->hasAttribute($offset);
	}

	public function offsetGet($offset)
	{
		return $this->getAttribute($offset);
	}

	public function offsetSet($offset, $value)
	{
		$this->addAttribute($offset, $value);
	}

	public function offsetUnset($offset)
	{
		$this->removeAtribute($offset);
	}

	/**
	 * @return string|null
	 */
	public function getContents()
	{
		if(NULL == $this->contents)
			$this->contents = $this->loadContents();
		return $this->contents;
	}

	abstract protected function loadContents();
}