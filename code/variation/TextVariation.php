<?php

/**
 * A variation that renders content editable text. Placeholders of the form {{property}} will be substituted
 * with the relevant value from the context provider.
 */
class TextVariation extends PersonalisationVariation {
	static $db = array(
		"Text" => "HTMLText"
	);

	function getCMSFields() {
		$fields = parent::getCMSFields();

		$text = new HtmlEditorField("Text", "Text Variation");
		$fields->push($text);

		return $fields;
	}

	function render(ContextProvider $context, Controller $controller = null) {
		$text = $this->Text;

		// substitute {{property}} references
		$i = 0;
		while(true) {
			if ($i >= strlen($text)) break;

			$i = strpos($text, "{{", $i);
			if ($i === FALSE) break;

			$j = strpos($text, "}}", $i);
			if ($j === FALSE) break;

			$name = substr($text, $i+2, $j - $i - 2);


			$values = $context->getProperties(array($name));
			$value = isset($values[$name]) ? $values[$name] : "";

			$text = substr($text, 0, $i) . $value . substr($text, $j+2);

			// after substituting, move $i by the number of characters we replaced. Even if $value is zero length,
			// we will have taken out the {{, so it's not infinite.
			$i += strlen($value);
		}
		return $text;
	}
}
