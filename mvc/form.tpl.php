<?php

// Auto-add the session token (ignored if not using sessions)
if(isset($validation) AND $error = $validation->error('token'))
{
	print html::tag('div', $error, array('class'=>'error form_error'));
}

//use WP nonce instead of session token (default nonce field name if none given)
$validation->create_nonce($action, v($nonce_field, 'mvc_nonce'));
/*
print html::tag('input', 0, array('type' => 'hidden', 'value' => Session::token(), 'name' => 'token'));
*/


foreach($sections as $section => $section_attributes)
{
	$section_attr_defaults = array(
		'class' => 'fieldset'
		, 'id' => "fs-$section"
	);
	
	print "<div". html::attributes( $section_attributes + $section_attr_defaults) . "><fieldset>";
	if(isset($section_attributes['label'])){ echo "<legend><span>".$section_attributes['label']."</span></legend>"; }
	foreach($fields[$section] as $field => $data)
	{
		//check for container details; override default tag?
		if( isset( $data['container'] ) && isset( $data['container']['tag'] )){
			$container = $data['container']['tag'];
			unset( $data['container']['tag'] );
		}
		else {
			$container = 'div';
		}
		print "\n\n<$container".(isset($data['container'])?html::attributes($data['container']):'').'>';
	
		//only add label if it's an "allowed" type, since by default the label is taken from the field id
		if(
			( ! in_array( $data['type'], array('checkbox', 'radio')) )
			&&
			( ! isset($data['attributes']['type']) OR ! in_array($data['attributes']['type'], array('hidden','submit','reset','button')) )
		){
			print html::tag('label', $data['label'], array('for'=>$data['attributes']['id']));
		}
	
		if($data['type'] === 'select') : // Select box
			print html::select($field, $data['options'], $data['value'], $data['attributes']);
			
		elseif('radio' === $data['type'] || 'checkbox' === $data['type']) : // check or radio bok
			print html::radios($field, $data['label'], $data['type'], $data['options'], $data['value'], $data['attributes']);

		elseif($data['type'] === 'textarea') : // Textarea
			print html::tag($data['type'], str($data['value']), $data['attributes']);
			
		elseif('datetime_manual' === $data['type'] || 'date_time' === $data['type']) : // Special datetime type; new override, since we can use html5 datatypes
			print html::datetime($data['value'], $field);
			
		else : // a normal input
			print html::tag($data['type'], 0, $data['attributes']+array('value' => str($data['value'])));
			
		endif; //type check
	
		if(isset($validation) AND $error = $validation->error($field))
		{
			print html::tag('div', $error, array('class'=>'form_error'));
		}
	
		if( isset( $data['description'] ) ):
			if( is_array($data['description']) ):
				//pop the description text, use the rest as attributes
				$text = $data['description']['text'];
				unset( $data['description']['text'] );
				
				echo html::tag('em', $text, $data['description']);
			else:
				echo html::tag('em', $data['description'],array('class'=>'description'));
			endif;	//description with attributes
		endif; //has description
		
		print "\n</$container>";
	} // !foreach fields
	print "\n</fieldset></div>";
} // !foreach section

?>
<script type="text/javascript">
(function($){
$(function(){
	Formlib.form.init( '<?php echo $attributes['id'] ?>', { inspect : 'parent', indicate_required : false } );
});
})(jQuery);
</script>
