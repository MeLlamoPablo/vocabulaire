var used_ids = [];
var deleted_rows = [];
var modes = {};
var examid;

function addRow(esp = '', fra = '', mode = 1, id = used_ids.length) {
	if(used_ids.indexOf(id) === -1){
		//The id used is not duplicate
		$('#editor').append(`
			<tr id="row${id}">
				<td><input class="form-control" required type="text" name="esp${id}" style="width: 95%;" data-required="true" value="${esp}"></td>
				<td><input class="form-control" required type="text" name="fra${id}" style="width: 95%;" data-required="true" value="${fra}"></td>
				<td>
					<div class="dropdown" id="dropdown${id}">
						<button class="btn btn-default dropdown-toggle" type="button" id="dropdown${id}_button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
							<span id="dropdown${id}_title">Se puede pedir en franc&eacute;s y en espa&ntilde;ol</span>
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu" aria-labelledby="dropdown${id}_button">
							<li><a href="#" class="dropdownOption" id="dropdown${id}_mode1">Se puede dar en franc&eacute;s y en espa&ntilde;ol</a></li>
							<li><a href="#" class="dropdownOption" id="dropdown${id}_mode2">Se da siempre en espa&ntilde;ol (se pide en franc&eacute;s)</a></li>
							<li><a href="#" class="dropdownOption" id="dropdown${id}_mode3">Se da siempre en franc&eacute;s (se pide en espa&ntilde;ol)</a></li>
						</ul>
					</div>
				</td>
				<td>
					<button type="button" class="btn btn-primary btn-xs btn-danger" id="delete${id}" onclick="deleteRow(${id})"
						    data-toggle="tooltip" data-placement="right" title="Borrar palabra">
						<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
					</button>
					<button style="display: none;" type="button" class="btn btn-primary btn-xs btn-success" id="undelete${id}" onclick="undeleteRow(${id})"
						    data-toggle="tooltip" data-placement="right" title="Conservar palabra">
						<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
					</button>
				</td>
			</tr>
		`);

		//We have to do this every time we add a new dropdownOption.
		$('.dropdownOption').click(event, onDropdownOptionClick);
		$('#dropdown' + id + '_mode' + mode).click(); //Set default mode
		$('[data-toggle="tooltip"]').tooltip();

		used_ids.push(id);

		return true;
	}else{
		console.error('Could not add row with id ' + id + '. That id is duplicate.');
		return false;
	}
		
}

function deleteRow(id){
	if($.inArray(id, used_ids) !== -1){
		if($('[name="fra' + id +'"]').val() !== ""
		   || $('[name="esp' + id +'"]').val() !== ""
		   || modes[id] !== 1){
			//If the row is not empty, it won't be deleted
			//Instead, it will be marked in red. Upon saving changes, the rows will be permanently deleted.

			$('#row' + id).addClass('danger');
			$('[name="fra' + id +'"], [name="esp' + id +'"]').attr('disabled', true);
			$('#dropdown' + id + ' > button').addClass('disabled');
			$('#delete' + id).css('display', 'none'); $('#undelete' + id).css('display', 'block');

			deleted_rows.push(id);
		}else{
			//If the row is empty, just delete it
			$('#row' + id).remove();

			//Also delete the default value from modes
			delete modes[id];
		}

		$('#main_form').trigger('rescan.areYouSure');
		return true;
	}else{
		console.error('Could not remove row with id ' + id + '. That row does not exist.');
		return false;
	}
}

function undeleteRow(id){
	var index = deleted_rows.indexOf(id);
	if(index !== -1){
		deleted_rows.splice(index, 1);

		$('#row' + id).removeClass('danger');
		$('[name="fra' + id +'"], [name="esp' + id +'"]').attr('disabled', false);
		$('#dropdown' + id + ' > button').removeClass('disabled');
		$('#delete' + id).css('display', 'block'); $('#undelete' + id).css('display', 'none');

		return true;
	}else{
		console.error('Could not undelete row with id ' + id + '. That row does not exist or is not marked as deleted.')
		return false;
	}
}

function onDropdownOptionClick(event){
	var regex = /dropdown([0-9]+)_mode([0-9]+)/;
	var matches = event.target.id.match(regex);
	modes[matches[1]] = +matches[2]; //The first match is the number of the row where the dropdown value was modified. 
									 //The second match is the dropdown value itself. We convert that from string to int.

	$('#dropdown' + matches[1] + '_title').html(event.target.text); //Change the dropdown title to the new option.

	//Workaround for jQuery.areYouSure not listening to dropdown changes.
	$('#workaround').val(JSON.stringify(modes));
	$('#main_form').trigger('rescan.areYouSure');
}

function send(){
	//Before sending, validate if every input has been filled
	var filled = true;
	$('[data-required="true"]').each(function(){
		if($(this).val() === '' && !$(this).prop('disabled')){
			filled = false;
			return false; //This only stpos the iteration, but doesn't make send() return false
		}
	});

	if(!filled){
		alert('Por favor, rellene todos los campos antes de guardar los cambios.')
		return false;
	}

	$('#sendButton').button('loading');

	var data = {};
	data['userid'] = userid;
	data['token'] = session_token;
	data['creating'] = creating.toString();
	data['title'] = $('#exam_title').val();
	if(!creating) data['examid'] = examid;

	data['questions'] = {}
	$.each(used_ids, function(k, v){
		if($.inArray(v, deleted_rows) === -1){
			data['questions'][v] = {
				fra: $('[name="fra' + v + '"]').val(),
				esp: $('[name="esp' + v + '"]').val(),
				mode: modes[v]
			}
		}
	});
	
	//If there are no questions (because every question is set to be deleted), don't send the form.
	//Let the user know that they can delete the whole exam from the admin dashboard instead.
	if(Object.getOwnPropertyNames(data['questions']).length === 0){
		alert('Ha marcado para borrar todas las preguntas del examen. Por favor, si desea borrar ' +
			'el examen, hágalo desde el panel de administración.');
		$('#sendButton').button('reset');
		return false;
	}

	$.post('examHandler.php', data, function(response){
		r = JSON.parse(response)

		if(r['success']){
			$('#main_form').trigger('reinitialize.areYouSure');

			//If we were creating an exam, we now need to enter edit mode
			creating = false; 
			examid = r['examid'];
			$('#titulo_examen').html('Editando <b>' + data['title'] + '</b>');

			//We also need to permanently remove any rows marked as deleted
			for(var i = 0; i < deleted_rows.length; i++){
				$('#row' + deleted_rows[i]).remove();
			}

			return true;
		}else{
			console.error('The form was not saved. Error returned: ' + r['error']);

			localStorage.setItem('vocabapp_last_error', r['error']);
			localStorage.setItem('vocabapp_last_exam_data', JSON.stringify(data));
			console.log('In order to minimize damage, the exam data that was intended to be written into' +
				' the database was saved into the local storage along with the last error message.');
			console.log('Access it with localStorage.vocabapp_last_exam_data and localStorage.vocabapp_last_error');

			$('#sendButton').removeClass('btn-primary');
			$('#sendButton').addClass('btn-danger');
			$('#sendButton').attr('value', 'Ha ocurrido un error :(');

			return false;
		}
	});
}

$(document).ready(function(){
	if(creating){
		addRow();

		//Set default values for modes and put them in the workaround input. Then rescan.
		modes[0] = 1; $('#workaround').val(JSON.stringify(modes)); $('#main_form').trigger('rescan.areYouSure');
	}else{
		//Insert all data
		examid = exam.id;
		$('#exam_title').val(exam.name);
		for(var i = 0; i < exam.questions.length; i++){
			addRow(exam.questions[i]['esp'], exam.questions[i]['fra'], exam.questions[i]['modo']);
		}
	}

	$('#main_form').areYouSure({
		'message': '¡Atención! Hay cambios no guardados. Si abandona esta página se perderán definitivamente.'
	});

	$('#main_form').on('dirty.areYouSure', function(){
		$('#sendButton').removeAttr('disabled');
		$('#sendButton').attr('value', 'Guardar cambios');
		$('#sendButton').addClass('btn-primary');
		$('#sendButton').removeClass('btn-success');

		$('#sendButton').button('reset');
	});
    $('#main_form').on('clean.areYouSure', function(){
    	$('#sendButton').attr('disabled', true);
		$('#sendButton').attr('value', 'Todos los cambios se han guardado');
		$('#sendButton').removeClass('btn-primary');
		$('#sendButton').addClass('btn-success');
    });

});