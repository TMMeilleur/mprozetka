{**
* 2017-2018 ASG Group
*
* MP Rozetka
*
* NOTICE OF LICENSE
*
* This source file is subject to the General Public License (GPL 2.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/GPL-2.0
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade the module to newer
* versions in the future.
*
*  @author    ASG Group (Alexander Grosul)
*  @copyright 2017-2018 ASG Group
*  @license   http://opensource.org/licenses/GPL-2.0 General Public License (GPL 2.0)
*}

{extends file="helpers/form/form.tpl"}
{block name="field"}
  {if $input.type == 'autocomplete'}
    {assign var="type" value="excluded_`$input.id`"}
    <input id="input{$input.id}" name="input{$input.id}" value="{if $fields_value[$type]}{foreach from=$fields_value[$type] item='item'}{$item.id}-{/foreach}{else}-{/if}" type="hidden">
    <input id="name{$input.id}" name="name{$input.id}" value="{if $fields_value[$type]}{foreach from=$fields_value[$type] item='item'}{$item.name}¤{/foreach}{else}¤{/if}" type="hidden">
    <div id="ajax_choose_{$input.id}">
      <div class="input-group">
        <input type="text" placeholder="{$input.descr}" id="{$input.id}_autocomplete_input" name="{$input.id}_autocomplete_input"/>
        <span class="input-group-addon"><i class="icon-search"></i></span>
      </div>
    </div>
    <div class="col-lg-3"></div>
    <div id="div{$input.id}" class="col-lg-9">
      {if $fields_value[$type]}
        {foreach from=$fields_value[$type] item='item'}
          <div class="form-control-static">
            <button type="button" class="btn btn-default del{$input.id}" name="{$item.id}">
              <i class="icon-remove text-danger"></i>
            </button>
            {$item.name}
          </div>
        {/foreach}
      {/if}
    </div>
  {/if}
  {$smarty.block.parent}
{/block}