<?php
class VSFormBuilder {
    
    public function __construct($options = array()) {
        $this->version = "1.0";
        $this->html = '';
        $this->global_exclude_fields = array('options', 'label', 'help_text', 'label_position', 'option_label_position','option_key');
        $this->required_attributes = array();
        $this->selected_values = array();
        $this->current_param = array();
        $this->formJson = array();
        $this->field_array = array(
            'exclude' => array(),
            'label' => '',
            'value' => '',
            'label_position' => 'before',
            'help_text' => '',
            'help_text_position' => 'after',
            'attributes' => array(),
            'attribute_html' => '',
        );
        
        $this->defaults = array(
            'parent_wrap_tag' => 'div',
            'parent_wrap_attributes' => array( 'class' => 'field-container', ),
            'field_wrap_tag' => 'div',
            'field_wrap_attributes' => array('class' => 'form-row', ), 
            'label_position' => 'before', # before | after,
            'label_tag' => 'label',
            'label_attributes' => array( 'class' => 'input-label', 'for' => array('id' => '{field_id}'), ),
            'help_text_tag' => 'span',
            'help_text_position' => 'after', # before | after,
            'help_text_attributes' => array( 'class' => 'help-text', 'id' => "{field_id}_help_text", ),
            'global_attributes' => array('id' => '','class' => '','name' => ''),
        );
        
        $this->settings = $this->_merge($this->defaults,$options);
    }
    
    public function set_selected_values($args = array()){
        $this->selected_values = $args;
    }
    
    public function get_selected_values($param){
        $key = '';
        if(isset($param['name'])){
            $key = $param['name'];
        }
        
        if(isset($param['option_key'])){
            $key = $param['option_key'];
        }
        
        if(isset($this->selected_values[$key])){
            return $this->selected_values[$key];
        }
        return false;
    }
    
    protected function _merge($defaults,$options,$force = false){
        if($force){ return array_merge_recursive($defaults,$options); }
        return array_merge($defaults,$options);
    } 
    
    public function set_options($options){
        if(!empty($options)){
            $this->settings = $this->_merge($this->defaults,$options);
        }
        return $this;
    }
    
    public function settings($key = '',$default = false){
        if(isset($this->settings[$key])){
            return $this->settings[$key];
        }        
        return $default;
    }
    
    private function _html($html){
        $this->html .= $html .' ';
    }
    
    public function add($type = 'text',$param = array()){
        $final_array = array('type' => $type,'param' => $param);
        $this->formJson[] = $final_array;
        return $this;
    }
    
    public function _array_to_html($data){
        $return_html = '';
        foreach($data as $id => $val){
            if(is_array($val)){
                $return_html .= ' '.$id.'="';
                foreach($val as $m){ $return_html .= ' '.$m.' '; }
                $return_html .= '" ';
                
            } else {
                $return_html .= ' '.$id.'="'.$val.'" ';
            }
        }
        
        return $return_html;
    }
    
    public function tag($type = 'open',$tag = '',$attributes = '',$value = ''){
        if('open' == $type){ return '<'.$tag.' '.$attributes.'>'; }
        else if('inline' == $type){ return '<'.$tag.' '.$attributes.'/>'; }
        else if('inline_value' == $type){ return '<'.$tag.' '.$attributes.'>'.$value.'</'.$tag.'>'; }
        else { return '</'.$tag.'>'; }
    }
    
    public function render_tag($type= '',$tag ='',$attributes = '',$value = ''){
        $this->_html($this->tag($type,$tag,$attributes,$value));
    }
    
    public function render_template_tags($main_arr,$data_arr){
        foreach($main_arr as $id => $val){
            if(is_array($val)){
                foreach($val as $i => $v){
                    $value = isset($data_arr['attributes']['id']) ? $data_arr['attributes']['id'] : "";
                    $main_arr[$id] = str_replace('{field_id}',$value,$main_arr[$id][$i]);
                }
            } else {
                $value = isset($data_arr['attributes']['id']) ? $data_arr['attributes']['id'] : "";
                $main_arr[$id] = str_replace('{field_id}',$value,$val);
            }
        }        
        return $main_arr;
    }
    
    public function field_label($attrs){
        $tag = $this->settings("label_tag");
        $position = $this->settings("label_position");
        if(isset($attrs['label_position'])){ $position = $attrs['label_position']; }        
        $label_attrs = $this->settings("label_attributes");
        $label_attrs = $this->render_template_tags($label_attrs,$attrs);
        $label_attrs = $this->_array_to_html($label_attrs);
        $label = $this->tag('inline_value',$tag,$label_attrs,$attrs['label']);
        return array('position' => $position,'html' => $label);
    }
    
    public function field_help_text($attrs){
        $tag = $this->settings("help_text_tag");
        $help_attributes = $this->settings("help_text_attributes");
        $help_attributes = $this->render_template_tags($help_attributes,$attrs);
        $help_attributes = $this->_array_to_html($help_attributes);
        $help_text = $this->tag('inline_value',$tag,$help_attributes,$attrs['help_text']);
        $position = $this->settings("help_text_position");
        if(isset($attrs['help_text_position'])){ $position = $attrs['help_text_position']; }
        return array('position' => $position,'html' => $help_text);
    }
    
    public function field_wrap($attributes,$type = 'field_wrap'){
        $wrap_attributes = $this->settings($type.'_attributes');
        $wrap_attributes = $this->render_template_tags($wrap_attributes,$attributes['attributes']);
        $wrap_attributes = $this->_array_to_html($wrap_attributes);
        return array( 'tag' => $this->settings($type.'_tag'), 'html' => $wrap_attributes, );
    }
    
    public function form_field_texts($type = '',$attributes){
        $function = 'field_'.$type;
        $data = $this->$function($attributes);
        $after = '';
        $before = '';
        if($data['position'] == 'before'){ $before = $data['html']; }
        else { $after = $data['html']; }
        return array($type."_before" => $before, $type.'_after' => $after, $type."_position" => $data['position']);
    }
    
    public function form_field($type = '',$tag = '',$attributes = '',$value = ''){
        $label_before = $label_after = $help_text_before = $help_text_after = $field_html = $label = '';
        $wrap = $this->field_wrap($attributes);
        if(!empty($attributes['label'])){ extract($this->form_field_texts('label',$attributes)); }        
        if(!empty($attributes['help_text'])){ extract($this->form_field_texts('help_text',$attributes)); }        
        if(empty($value)){ $value = isset($attributes['value']) ? $attributes['value'] : $value; }
        $field_html = $this->tag($type,$tag,$attributes['attribute_html'],$value);
        $final_html = $label_before.' '.$help_text_before.' '.$field_html.' '.$help_text_after.' '.$label_after;
        $this->render_tag('inline_value',$wrap['tag'],$wrap['html'],$final_html);
        return $this;
    }
    
    public function fix_attributes($type,$data2){
        $exchange_data = array('id' => time().rand(1,20),'class' => 'input-'.$type);
        foreach($exchange_data as $i => $v){
            if(!isset($data2['attributes'][$i]) || empty($data2['attributes'][$i])){
                $data2['attributes'][$i] =  $v;
            }
        }
        
        if(!isset($data2['attributes']['name']) || empty($data2['attributes']['name'])){
            $data2['attributes']['name'] = $data2['attributes']['id'];
        }
        
        return $data2;
    }
    
    public function get_field_attributes($type = '',$param = array()){
        $attributes = $param;
        foreach($this->global_exclude_fields as $att){ unset($attributes[$att]); }
        $fa = $this->field_array;
        $fa['attributes'] = $this->settings("global_attributes");
        if(!empty($attributes)){ $fa['attributes'] = $this->_merge($fa['attributes'],$attributes);}        
        $data = $this->_merge($fa,$param);
        
        $data = $this->fix_attributes($type,$data);
        $data['attributes'] = array_filter($data['attributes']);
        return $data;
    }

    public function generate(){
        foreach($this->formJson as $i => $v){
            $type = $v['type'];
            $this->current_param = $v['param'];
            $this->$type();
        }
        return $this->html;
    }
    
    public function form_start($param = array()){
        if(empty($param)){$param = $this->current_param;}
        $attributes = $this->get_field_attributes('textarea',$param);
        $attributes['attribute_html'] = $this->_array_to_html($attributes['attributes']);
        $this->render_tag("open",'form',$attributes['attribute_html']);
        return $this;
    }
    
    public function form_end($param = array()){
        $this->render_tag("close",'form');
        return $this;
    }
    
    public function fieldset_start($param = array()){
        if(empty($param)){$param = $this->current_param;}
        $attributes = $this->get_field_attributes('fieldset',$param);
        $attributes['attribute_html'] = $this->_array_to_html($attributes['attributes']);
        $this->render_tag("open",'fieldset',$attributes['attribute_html']);
        if(isset($attributes['label'])){ $this->render_tag("inline_value",'legend','',$attributes['label']); }
        return $this;
    }
    
    public function fieldset_end($param = array()){
        $this->render_tag("close",'fieldset');
        return $this;
    } 
    
    public function input_field($type,$param = array()){
        if(empty($param)){ $param = $this->current_param; }        
        $attributes_array = $this->get_field_attributes($type,$param);
        $attributes_array['attributes']['type'] = $type;
        $attributes_array['attributes']['value'] = isset($param['value']) ? $param['value'] : $this->get_selected_values($param);
        $attributes_array['attribute_html'] = $this->_array_to_html($attributes_array['attributes']);
        $this->form_field('inline','input',$attributes_array);
        return $this;        
    }
    
    public function _options($options,$attrs,$selected = ''){
        $html = '';
        foreach($options as $option_id => $option_value){
            if(is_array($option_value)){
                if(isset($option_value['group_name'])){
                    $attr_html = 'label="'.$option_value['group_name'].'" ';
                    if(isset($option_value['disabled'])){ $attr_html .= ' disabled="disabled" '; }                    
                    $html .= $this->tag('open','optgroup',$attr_html);
                    $html .= $this->_options($option_value['options'],$attrs,$selected);
                    $html .= $this->tag('close','optgroup');
                } else {
                    $attributes = array('value' => $option_id);
                    if(isset($option_value['attributes'])){ $attributes = $this->_merge($attributes,$option_value['attributes']); }
                    if(in_array($option_id,$selected)){ $attributes['selected'] = true; }
                    $param = array('attributes' => $attributes);
                    $attributes = $this->get_field_attributes("option",$param);
                    $attributes = $this->_array_to_html($attributes['attributes']);
                    $html .= $this->tag('inline_value','option',$attributes,$option_value['value']);
                }
            } else {
                $param = array('attributes' => array("value" => $option_id));
                if(in_array($option_id,$selected)){ $param['attributes']['selected'] = true; }                
                $attributes = $this->get_field_attributes("option",$param);
                $attributes = $this->_array_to_html($attributes['attributes']);
                $html .= $this->tag('inline_value','option',$attributes,$option_value);   
            }
        }
        
        return $html;
    }
    
    public function _is_checked($param,$type = ''){
        if(!isset($param['value'])){$param['value'] = 'yes';}
        $param['selected'] = isset($param['selected']) ? $param['selected'] : $this->get_selected_values($param);
        if(isset($param['selected'])){
            if($param['selected'] == $param['value']){
                $param['checked'] = 'checked';
            }
        }
        
        return $param;
    }
    
    public function check_radio($type = '',$param =  array()){
        if(empty($param)){ $param = $this->current_param; }
        $attrs = $this->get_field_attributes($type,$param);
        $label_before = $label_after = $help_text_before = $help_text_after = $field_html = $label = '';
        $wrap = $this->field_wrap($attrs,'parent_wrap');
        $this->render_tag("open",$wrap['tag'],$wrap['html']);
        if(!empty($attrs['label'])){ extract($this->form_field_texts('label',$attrs)); }        
        if(!empty($attrs['help_text'])){ extract($this->form_field_texts('help_text',$attrs)); }        
        $this->_html($label_before);
        $this->_html($help_text_before);
        
        foreach($attrs['options'] as $id => $Mval){
            $attributes = $attrs;
            $val = $Mval;
            
            if(is_array($Mval)){
                $attributes['attributes'] = $this->_merge($attributes['attributes'],$Mval['attributes']);
                $val = $Mval['value'];
            }
            
            $attributes['attributes']['id'] = $type.'-'.$id;
            
            if($type == 'checkbox'){
                if(!isset($attributes['attributes']['name']) || empty($attributes['attributes']['name'])){
                    $attributes['attributes']['name']= $attributes['attributes']['id'].'[]';
                }                
            }
            
            $attributes['attributes']['value'] = $id;
            $attributes['attributes']['type'] = $type;
            
            if(!isset($attributes['selected'])){
                $attributes['selected'] = $this->get_selected_values($param);
            } 
            
            if($attributes['selected'] == $id){ $attributes['attributes']['checked'] =  true; }
            $attributes['label'] = $val;
            if(isset($param['option_label_position'])){ $attributes['label_position'] = $param['option_label_position']; }
            $attributes['attribute_html'] = $this->_array_to_html($attributes['attributes']);
            $this->form_field("inline",'input',$attributes);
        }
        
        $this->_html($help_text_after);
        $this->_html($label_after);
        $this->render_tag("end",$wrap['tag'],$wrap['html']);
        return $this;
    }
    
    public function textarea($param = array()){
        if(empty($param)){ $param = $this->current_param; }
        $attributes_array = $this->get_field_attributes('textarea',$param);
        $attributes_array['attributes']['value'] = isset($param['value']) ? $param['value'] : $this->get_selected_values($param['name']);
        $attributes_array['attribute_html'] = $this->_array_to_html($attributes_array['attributes']);
        $this->form_field('inline_value','textarea',$attributes_array);
        return $this;        
    }
    
    public function select($param = array()){
        if(empty($param)){ $param = $this->current_param; }        
        $attrs = $this->get_field_attributes('select',$param);
        
        $selected = isset($attrs['selected']) ? $attrs['selected'] : $this->get_selected_values($param);
        $options = $attrs['options'];
        unset($attrs['options']);
        
        if(!is_array($selected)){$selected = array($selected);}        
        $option_html = $this->_options($options,$attrs,$selected);
        $attrs['attribute_html'] = $this->_array_to_html($attrs['attributes']);
        $this->form_field('inline_value','select',$attrs,$option_html);
        return $this;
    }
    
    public function text($param = array()){ return $this->input_field("text",$param);}
    
    public function password($param = array()){ return $this->input_field("password",$param);}
    
    public function search($param = array()){ return $this->input_field("search",$param);}
    
    public function email($param = array()){ return $this->input_field("email",$param);}
    
    public function url($param = array()){ return $this->input_field("url",$param);}
    
    public function tel($param = array()){ return $this->input_field("tel",$param);}
    
    public function number($param = array()){ return $this->input_field("number",$param);}
    
    public function range($param = array()){ return $this->input_field("range",$param);}
    
    public function date($param = array()){ return $this->input_field("date",$param);}
    
    public function month($param = array()){ return $this->input_field("month",$param);}
    
    public function week($param = array()){ return $this->input_field("week",$param);}
    
    public function time($param = array()){ return $this->input_field("time",$param);}
    
    public function datetime($param = array()){ return $this->input_field("datetime",$param);}
    
    public function datetime_local($param = array()){ return $this->input_field("datetime_local",$param);}
    
    public function color($param = array()){ return $this->input_field("color",$param);}
    
    public function file($param = array()){ return $this->input_field("file",$param);}
    
    public function radio($param = array()){ 
        if(empty($param)){ $param = $this->current_param; } 
        $param = $this->_is_checked($param,'radio');
        return $this->input_field("radio",$param);
    }
    
    public function checkbox($param = array()){ 
        if(empty($param)){ $param = $this->current_param; } 
        $param = $this->_is_checked($param,'checkbox');
        return $this->input_field("checkbox",$param);
    }
    
    public function radio_group($param = array()){ return $this->check_radio("radio",$param);}
    
    public function checkbox_group($param = array()){ return $this->check_radio("checkbox",$param);}
    
    public function submit($param = array()){ return $this->input_field('submit',$param);}
}
