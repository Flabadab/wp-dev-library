<?php
/*
 * Based on lesson from http://www.devshed.com/c/a/PHP/Plugin-Pattern-in-PHP-and-JavaScript/
 */

#namespace Render;


interface Renderable {
	public function render();
}

abstract class AbstractHtml{
	protected $_content;
	protected $_id;
	protected $_class;
	
	protected $_cached;
	
	/**
	 * Constructor
	 */
	public function __construct($content, $id = false, $class = false) {
		#if (is_string($content)) {
			$this->_content = $content;
		#}
		if (is_string($id) && !empty($id)) {
			$this->_id = $id;
		}
		if (is_string($class) && !empty($class)) {
			$this->_class = $class;
		}
	}
	
}

#region ---------------------- HTML Content Elements -----------------------

class GenericContentHtml extends AbstractHtml implements Renderable {
	/**
	 * Render the generic element, given the tag
	 */
	function renderFor($tag) {
		//if already rendered
		if(!empty($this->_cached)){ return $this->_cached; }
		
		$this->_cached = '<'.$tag;
		if ($this->_id) {
			$this->_cached .= ' id="' . $this->_id . '"';
		}
		if ($this->_class) {
		   $this->_cached .= ' class="' . $this->_class . '"';
		}
		$this->_cached .= '>' . (is_a($this->_content, 'Renderable') ? $this->_content->render() : $this->_content) . "</$tag>\n";
		return $this->_cached;
	}
	
	public function render(){ }
}

class P extends GenericContentHtml {
	/**
	 * Render the Paragraph element
	 */
	public function render() {
		return parent::renderFor('p');
	}
}
class Em extends GenericContentHtml {
	/**
	 * Render the Emphasized element
	 */
	public function render() {
		return parent::renderFor('em');
	}
}
class Div extends GenericContentHtml {
	/**
	 * Render the Emphasized element
	 */
	public function render() {
		return parent::renderFor('div');
	}

}#endregion ---------------------- HTML Content Elements -----------------------

#region ---------------------- FIELDS -----------------------

class Label extends AbstractHtml implements Renderable {
	protected $_isFieldLabel;
	
	/**
	 * Allows toggle for display style vs. field style
	 * @param $content
	 * @param $id
	 * @param $class
	 * @param $isFieldLabel
	 */
	public function __construct($content, $id = false, $class = false, $isFieldLabel = false){
		$this->_isFieldLabel = $isFieldLabel;
		parent::__construct($content, $id, $class);
	}
	
	public function render(){
		//if already rendered
		if(!empty($this->_cached)){ return $this->_cached; }
		
		//use an actual label
		if($this->_isFieldLabel === false){
			$tag = 'label';
			$idMask = ' for="%s"';
		}
		//create a div rel to something else, linked by id
		else{
			$tag = 'div';
			$idMask = ' id="lbl-%1$s" rel="%1$s"';
		}
		
		$this->_cached = '<'.$tag;
		if($this->_id){
			$this->_cached .= sprintf($idMask, $this->_id);
		}
		if($this->_class){
			$this->_cached .= ' class="'.$this->_class.'"';
		}
		$this->_cached .= '>'.$this->_content."</$tag>\n";
		return $this->_cached;
	}
}//----	end class Label

class Textbox extends AbstractHtml implements Renderable {
	
	public function render(){
		//if already rendered
		if(!empty($this->_cached)){ return $this->_cached; }
		
		$this->_cached = sprintf('<input id="%1$s" name="%1$s" class="text%2$s" value="%3$s" />'
			,$this->_id
			,($this->_class ? ' '.$this->_class : '')
			,$this->_content
		);
		
		return $this->_cached;
	}
}//----	end class Textbox

class Select extends AbstractHtml implements Renderable {
	protected $_value;
	
	/**
	 * SAdd the default value
	 * @param $content
	 * @param $id
	 * @param $class
	 * @param $value
	 */
	public function __construct($content, $id = false, $class = false, $value = false){
		$this->_value = $value;
		parent::__construct($content, $id, $class);
	}
	
	public function render(){
		//if already rendered
		if(!empty($this->_cached)){ return $this->_cached; }
		
		$renderedOptions = '';
		foreach($this->_content as $key => $label){
			$isChecked = ($this->_value == $key);
			$renderedOptions .= sprintf('<option value="%s"%s>%s</option>'
				, $key
				, $isChecked ? ' selected="selected"' : ''
				, ($label === false ? $key : $label));
		}
		$this->_cached = sprintf('<select id="%1$s" name="%1$s" class="select%2$s">%3$s</select>'
			,$this->_id
			,($this->_class ? ' '.$this->_class : '')
			,$renderedOptions
		);
		
		return $this->_cached;
	}
}//----	end class Select

class Radios extends AbstractHtml implements Renderable {
	protected $_value;
	
	/**
	 * SAdd the default value
	 * @param $content
	 * @param $id
	 * @param $class
	 * @param $value
	 */
	public function __construct($content, $id = false, $class = false, $value = false){
		$this->_value = $value;
		parent::__construct($content, $id, $class);
	}
		
	public function render(){
		//if already rendered
		if(!empty($this->_cached)){ return $this->_cached; }
		
		$renderedOptions = '';
		$counter = 0;
		foreach($this->_content as $key => $label){
			$isChecked = ($this->_value == $key);
			$renderedOptions .= sprintf('<input type="radio" value="%3$s" name="%1$s" id="%1$s-o%4$d" %5$s/><label for="%1$s-o%4$d">%2$s</label>'."\n"
				, $this->_id
				, ($label === false ? $key : $label)
				, $key
				, $counter++
				, $isChecked ? 'checked="checked" ' : ''
			);
		}
		$this->_cached = sprintf('<div class="fs-wrap" id="%1$s"%2$s><fieldset>%3$s</fieldset></div>'
			,$this->_id
			,($this->_class ? ' class="'.$this->_class.'"' : '')
			,$renderedOptions
		);
		
		return $this->_cached;
	}
}//----	end class Select

class Field extends AbstractHtml implements Renderable {
	protected $_label;
	protected $_description;
	
	public function __construct($id = false, $class = false, $description = false, Renderable $label = NULL, Renderable $input = NULL, $value = false){
		if(empty($id)){
			throw new RenderableException("Arguments not provided: id.");
		}
		elseif(empty($label) && empty($description)){
			throw new RenderableException("Arguments not provided: label.");
		}
		
		//default label with description as text
		if(empty($label)){
			$label = new Label($description, $id, $class);
		}
		
		//default input
		if($input === NULL){
			$input = new Textbox($value, $id, $class);
		}
		
		$this->_id = 'f-'.$id;
		$this->_class = $class;
		$this->_label = $label;
		$this->_content = $input;
		$this->_description = $description;
	}
	
	public function render(){
		//if already rendered
		if(!empty($this->_cached)){ return $this->_cached; }
		
		$inner = $this->_label->render() . "\n" . $this->_content->render() . "\n";
		if(!empty($this->_description)){
			$em = new Em($this->_description, false, 'description');
			$inner .= $em->render();
		}
		$output = new Div($inner, $this->_id, 'f-wrap '.$this->_class);
		$this->_cached = $output->render();
		return $this->_cached;
	}
}

class Renderer {
	protected $_elements = array();

	/**
	 * Add a single renderable element
	 */
	public function append(Renderable $element) {
		$this->_elements[] = $element;
		return $this;
	}
	/**
	 * Add multiple renderable elements
	 */
	public function appends(array $elements) {
		if (!empty($elements)) {
			foreach ($elements as $element) {
				$this->append($element);
			}
		}
	}

	
	/**
	 * Remove a renderable element
	 */
	public function remove(Renderable $element) {
		if (in_array($element, $this->_elements, true)) {
			$elements = array();
			foreach ($this->_elements as $_element) {
				if ($element !== $_element) {
					$elements[] = $_element;
				}
			}
			$this->_elements = $elements;
		}
	}

	/**
	 * Render all the inputted renderable elements
	 */
	public function render() {
		$output = '';
		if (!empty($this->_elements)) {
			foreach ($this->_elements as $_element) {
				$output .= $_element->render();
			}
		}
		return $output;
	}	
}//-----	end class Renderer


/**
 * Specific exception for Renderable class
 * @author jeremys
 *
 */
class RenderableException extends Exception { }
?>