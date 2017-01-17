<?php
/**
 * Magmodules.eu - http://www.magmodules.eu
 *
 * NOTICE OF LICENSE
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@magmodules.eu so we can send you a copy immediately.
 *
 * @category      Magmodules
 * @package       Magmodules_Channable
 * @author        Magmodules <info@magmodules.eu)
 * @copyright     Copyright (c) 2017 (http://www.magmodules.eu)
 * @license       http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */

class Magmodules_Channable_Block_Adminhtml_Config_Form_Renderer_Select extends Mage_Core_Block_Html_Select
{

    /**
     * @param $inputName
     *
     * @return $this
     */
    public function setInputName($inputName)
    {
        $this->setData('inputname', $inputName);

        return $this;
    }

    /**
     * @param $columnName
     *
     * @return $this
     */
    public function setColumnName($columnName)
    {
        $this->setData('columnname', $columnName);

        return $this;
    }

    /**
     * @param $column
     *
     * @return $this
     */
    public function setColumn($column)
    {
        $this->setData('column', $column);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHtml()
    {
        return $this->toHtml();
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_beforeToHtml()) {
            return '';
        }

        $html = sprintf(
            '<select name="%s" class="%s" %s>',
            $this->getInputName(),
            $this->getClass(),
            $this->getExtraParams()
        );

        $values = $this->getValue();

        if (!is_array($values)) {
            if (!empty($values)) {
                $values = array($values);
            } else {
                $values = array();
            }
        }

        $isArrayOption = true;

        foreach ($this->getOptions() as $key => $option) {
            if ($isArrayOption && is_array($option)) {
                $value = $option['value'];
                $label = $option['label'];
                $params = (!empty($option['params'])) ? $option['params'] : array();
            } else {
                $value = $key;
                $label = $option;
                $isArrayOption = false;
                $params = array();
            }

            if (is_array($value)) {
                $html .= '<optgroup label="' . $label . '">';
                foreach ($value as $keyGroup => $optionGroup) {
                    if (!is_array($optionGroup)) {
                        $optionGroup = array(
                            'value' => $keyGroup,
                            'label' => $optionGroup
                        );
                    }

                    $html .= $this->_optionToHtml($optionGroup, in_array($optionGroup['value'], $values));
                }

                $html .= '</optgroup>';
            } else {
                $html .= $this->_optionToHtml(
                    array(
                        'value'  => $value,
                        'label'  => $label,
                        'params' => $params
                    ), in_array($value, $values)
                );
            }
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * @return mixed
     */
    public function getInputName()
    {
        return $this->getData('inputname');
    }

    /**
     * @return string
     */
    public function getExtraParams()
    {
        $column = $this->getColumn();
        if ($column && isset($column['style'])) {
            return ' style="' . $column['style'] . '" ';
        } else {
            return '';
        }
    }

    /**
     * @return mixed
     */
    public function getColumn()
    {
        return $this->getData('column');
    }

    /**
     * @param      $option
     * @param bool $selected
     *
     * @return string
     */
    protected function _optionToHtml($option, $selected = false)
    {
        $selectedHtml = $selected ? ' selected="selected"' : '';
        if ($this->getIsRenderToJsTemplate() === true) {
            $selectedHtml .= ' #{option_extra_attr_' . self::calcOptionHash($option['value']) . '}';
        }

        $params = '';
        if (!empty($option['params']) && is_array($option['params'])) {
            foreach ($option['params'] as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $keyMulti => $valueMulti) {
                        $params .= sprintf(' %s="%s" ', $keyMulti, $valueMulti);
                    }
                } else {
                    $params .= sprintf(' %s="%s" ', $key, $value);
                }
            }
        }

        return sprintf(
            '<option value="%s"%s %s>%s</option>',
            $this->escapeHtml($option['value']),
            $selectedHtml,
            $params, $this->escapeHtml($option['label'])
        );
    }

    /**
     * @param $optionValue
     *
     * @return string
     */
    public function calcOptionHash($optionValue)
    {
        return sprintf('%u', crc32($this->getColumnName() . $this->getInputName() . $optionValue));
    }

    /**
     * @return mixed
     */
    public function getColumnName()
    {
        return $this->getData('columnname');
    }

}