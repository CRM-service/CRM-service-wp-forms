/*! wordpress-plugin 06-04-2018 10:26 - Digitoimisto Dude */
jQuery(document).ready(function($){function disable_module_fields_used(){$(".crmservice-metabox-connections-wrap .row-field .col-module select").each(function(){"0"!==this.value&&$('.crmservice-metabox-connections-wrap .row-field .col-module select option[value="'+this.value+'"]').attr("disabled","true")})}function populate_form_fields(){console.log(form_fields),x=0,$.each(form_fields,function(i,item){row=$(".crmservice-metabox-connections-wrap .row-field-base").clone(),row=$(row),row.removeAttr("style").removeClass("row-field-base"),row.attr("id","form-row-"+i).find(".col-form p").html(item),row.find('input[name="form_field"]').attr("name","crmservice_connections["+x+"][form_field]").val(i),row.find('[name="module_field"]').attr("name","crmservice_connections["+x+"][module_field]"),$(".crmservice-metabox-connections-wrap").append(row),x++})}function populate_module_fields(){console.log(module_fields),$.each(module_fields,function(i,item){$(".crmservice-metabox-connections-wrap .col-module select").append($("<option>",{value:item.name,text:item.label}))})}var form_fields=[],module_fields=[];$(document).on("change",".crmservice-metabox-connections-wrap .row-field .col-module select",function(){$(this).parent(".col-module").find("input").val(this.value),$(".crmservice-metabox-connections-wrap .row-field .col-module select option").attr("disabled",!1),disable_module_fields_used()}),disable_module_fields_used(),$(document).on("change",'select[name="crmservice_form"]',function(){$(".crmservice-metabox-connections-wrap .row-field:not(.row-field-base)").remove(),console.log(this.value),$.ajax({type:"GET",dataType:"json",data:{form:this.value},url:crmservice.root+"crmservice/v1/form/fields",beforeSend:function(xhr){xhr.setRequestHeader("X-WP-Nonce",crmservice.nonce)},success:function(data){form_fields=data.fields,populate_form_fields()}})}),$(document).on("change",'select[name="crmservice_module"]',function(){$(".crmservice-metabox-connections-wrap .col-module select").empty().append("<option>Select</option>"),console.log(this.value),$.ajax({type:"GET",dataType:"json",data:{module:this.value},url:crmservice.root+"crmservice/v1/module/fields",beforeSend:function(xhr){xhr.setRequestHeader("X-WP-Nonce",crmservice.nonce)},success:function(data){module_fields=data,populate_module_fields()}})})});