<?php namespace App\Services;

/**
 * App Form Class - 10 Dec 2023
 * 
 * @author Neels Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.1 - DEV - 16 Dec 2023
 *   - Improve option() method.
 *   - Tabs to spaces.
 *
 */

class AppForm {

  public function input( $type, $model, $fieldName, $options = [] ) {
    $id = $fieldName;
    $name = $fieldName;
    $val = $model ? $model->$fieldName ?? null : null;
    $format = $options['format'] ?? null;
    $required = ( $options['required'] ?? false ) ? ' required' : '';
    $readonly = ( $options['readonly'] ?? false ) ? ' readonly' : '';
    $disabled = ( $options['disabled'] ?? false ) ? ' disabled' : '';
    $onchange = $options['onchange'] ?? '';
    switch ( $format ) {
      case 'int': $val = (int) $val; break;
  
      case 'currency':
        $val = currency( (float) $val, 'R ' );
        $onchange = ' onchange="this.value=F1.lib.Utils.currency(this.value)"';
        break;

      default: $val = escape( $val );
    }
    switch ( $type ) {
      case 'textarea': return "<textarea id=\"$id\" name=\"$name\"$required$readonly$disabled>$val</textarea>\n";
      default: return "<input type=\"$type\" id=\"$id\" name=\"$name\" value=\"$val\"$onchange$required$readonly$disabled>\n";
    }
  }


  public function option( $value, $label = '', $selectedValue = null, $title = '', $key = null ) {
    $label = $label ?: $value;
    $valueAttr = ( $value === $label ) ? '' : " value=\"$value\"";
    $selectedAttr = ( $value == $selectedValue ) ? ' selected' : '';
    $titleAttr = $title ? ' title="' . escape( ( $title === 'html' ) ? $label :$title ) . '"' : '';
    $keyAttr = $key ? ' data-key="' . $key . '"' : '';
    return "<option$valueAttr$titleAttr$selectedAttr$keyAttr>" . ( ( $title === 'html' ) 
      ? strip_tags( $label ) : $label ) . '</option>' . PHP_EOL;
  }

} // AppForm