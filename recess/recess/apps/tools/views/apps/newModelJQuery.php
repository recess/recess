<script language="javascript">
	window.onload = function() {
		dp.SyntaxHighlighter.ClipboardSwf = '<?php echo $_ENV['url.assetbase']; ?>flash/clipboard.swf';
		dp.SyntaxHighlighter.HighlightAll('code');
		
		var newModel  =  function () {
			var do_extension  =  '';//'.json';
			function addField() {
				isFirstField = $("#propertiesForm .removeField:last").length == 0;
				
				$("#propertiesForm .removeField:last").unbind('blur');
				newField = $("#propertyTemplate").children().clone();
				newField.appendTo("#propertiesForm");
				newField.children(".fieldName").val("test");
				$("#propertiesForm .removeField:last").click(function() { 
					$(this).parent().parent().remove(); 
					colorTables();
				});
				setPropertyFocus();
				colorTables();
				
				if(isFirstField) {
					fieldTr = $("#propertiesForm .fieldName:last").parent().parent();
					fieldTr.find(".fieldName").val("id");
					fieldTr.find(".type").val("Integer Autoincrement");
					fieldTr.find(".primaryKey").attr("checked","checked");
				}
			}

			$("#propertiesForm").children().appendTo("#propertyTemplate");
			$("#propertyTemplate .fieldName").val("");
			$("#relationsForm").children().appendTo("#relationTemplate");
			$("#tableOptions :radio").removeAttr("checked");
			disablePropertiesForm();
			
			function addRelation() {
				$("#relationTemplate").children().clone().appendTo("#relationsForm");
				$("#relationsForm .removeRelation:last").click(function() { $(this).parent().parent().remove(); });
				$("#relationsForm a").click( function() {
					$(this).siblings().show();
					$(this).remove();
					colorTables();
				});
				$("#relationsForm .relationName:last").focus();
				colorTables();
			}
			
			function colorTables() {
				$("#relationsForm").children(":odd").css("background-color", "#f5f5fa");
				$("#relationsForm").children(":even").css("background-color", "#fff");
				$("#propertiesForm").children(":odd").css("background-color", "#f5f5fa");
				$("#propertiesForm").children(":even").css("background-color", "#fff");
			}
			
			function setPropertyFocus() {
				$("#propertiesForm .removeField:last").blur( addField );
				$("#propertiesForm .fieldName:last").focus();
			}
			
			$("#tableOptions :radio").change( function() {
				if($(this).attr("checked") == true) {
					$("#tableOptions table").css({display: "none"});
					$("#tableOptions table :input").attr("disabled","disabled");
					$(this).parent().parent().children("table").css({display: "block"});
					$(this).parent().parent().find(":input").removeAttr("disabled");
					if($(this).val() == "no") {
						enablePropertiesForm();
					} else {
						disablePropertiesForm();
						if($("#existingTableName").val() == "") {
							clearProperties();
							$("#existingTableName").css('color', 'black');
							$("#existingTableName").css('background-color', 'yellow');
						} else {
							fillPropertiesFromExistingSource();
						}
					}
				}
			} );
			
			$("#createTable").change( function() {
				if($(this).val() == "Yes") {
					$("#tableNameRow").show();
				} else {
					$("#tableNameRow").hide();
				}
			});
			
			$(".addField").click( addField );
			$(".addRelation").click( addRelation );
			
			$("#propertiesForm .removeField:last").blur( addField );
			$("#modelName").focus();
			
			$("#propertiesForm .removeField:last").click(function() { $(this).parent().parent().remove(); });
			
			// Check Model Name and Prefill Table
			$("#modelName").blur( function () {
				if($(this).val() != "" ) {
					jQuery.getJSON("<?php echo $controller->urlTo('analyzeModelName', ''); ?>" + $(this).val() + do_extension,
									null,
									function(data) {
											if(!data.isValid) {
												$("#modelName").css('color', 'white');
												$("#modelName").css('background-color', 'red');
											} else {
												$("#modelName").css('color', 'black');
												$("#modelName").css('background-color', '#0f0');
												$("#tableName").val(data.tableName);
											}
									});
				} else {
					$(this).css('color', 'black');
					$(this).css('background-color', 'yellow');
				}
			});
			
			$("#modelName").focus( function() {
				$(this).css('color', 'black');
				$(this).css('background-color', 'white');
			});
			
			$("#existingTableName").change( function () {
				if($(this).children(":first").val() == "") {
					$(this).children(":first").remove();
				}
				if($(this).val() != "") {
					fillPropertiesFromExistingSource();
					$("#existingTableName").css('color', 'black');
					$("#existingTableName").css('background-color', '#0f0');
				} else {
					$("#existingTableName").css('color', 'black');
					$("#existingTableName").css('background-color', 'yellow');
				}
			});
			
			
			$("#existingDataSource").change( function() {
				loadTables();
			});
			
			function loadTables() {
				clearProperties();
				$("#existingTableName").children().remove();
				$("#existingTableName").append("<option value=\"\"></option>");
				$("#existingTableName").val("").css('color','black').css('background','yellow');
				jQuery.getJSON(
						"<?php echo $_ENV['url.base']; ?>recess/apps/model/gen/getTables/" + $("#existingDataSource").val() + do_extension,
						null,
						function(data) {
							for (var i in data.tables) {
								$("#existingTableName").append("<option value=\"" + data.tables[i] + "\">" + data.tables[i] + "</option>");
							}
						}
					);
			}
			
			
			
			function clearProperties() {
				$("#propertiesForm").children().remove();
			}
			
			function disablePropertiesForm() {
				$("#propertiesForm :input").attr("disabled", "disabled");
				$("#propertyTemplate :input").attr("disabled", "disabled");
				$(".addField").hide();
			}
			
			function enablePropertiesForm() {
				$("#propertiesForm :input").removeAttr("disabled");
				$("#propertyTemplate :input").removeAttr("disabled");
				$(".addField").show();
				isEmpty = $("#propertiesForm .removeField:last").length == 0;
				if(isEmpty) {
					addField();
					addField();
				}
				$("#tableName").focus();
			}
			
			function addProperty(name, type, isPrimaryKey, options) {
				addField();
				propertiesForm = $("#propertiesForm tr:last");
				propertiesForm.find(".fieldName").val(name);
				propertiesForm.find(".type").val(type);
				if(isPrimaryKey)
					propertiesForm.find(":radio").attr("checked", "checked");
				if(options.autoincrement != null) {
					if(options.autoincrement == true) {
						propertiesForm.find(".type").val("Integer Autoincrement");
					}
				}
				$("#propertiesForm").parent().find("button").focus();
			}
			
			function fillPropertiesFromExistingSource() {
				jQuery.getJSON(
						"<?php echo $_ENV['url.base']; ?>recess/apps/model/gen/getTableProps/" + $("#existingDataSource").val() + "/" + $("#existingTableName").val() + do_extension,
						null,
						function(data) {
							clearProperties();
							for (var i in data.columns) {
								addProperty(data.columns[i].name,
											data.columns[i].type,
											data.columns[i].isPrimaryKey,
											data.columns[i].options);
							}
						}
					);
			}
			
			$("form").submit( function() {
				$("#propertiesForm :input").removeAttr("disabled");
				checked = $("#propertiesForm :checked");
				if(checked.length == 1) {
					checked.val(checked.parent().parent().find(".fieldName").val());
				}
			} );
			
		}();
			
	}
</script>
<?php echo Html::js('jquery/jquery-1.2.6') ?>
