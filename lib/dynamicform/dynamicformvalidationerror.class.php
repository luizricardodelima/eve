<?php

/**
 * This class defines the possible validation errors that are listed in the
 * validate() methods of DynamicForm and DynamicFormItem classes.
 */
class DynamicFormValidationError
{
    const MANDATORY =           'dynamicform.validation.error.mandatory';
	const UNDER_MIN_WORDS =     'dynamicform.validation.error.under.min.words';
	const OVER_MAX_WORDS =      'dynamicform.validation.error.over.max.words';
	const FILE_ERROR =          'dynamicform.validation.error.file.error';
	const FILE_EXCEEDED_SIZE =  'dynamicform.validation.error.file.exceeded.size';
	const FILE_WRONG_TYPE =     'dynamicform.validation.error.file.wrong.type';
	const FILE_UPLOAD_ERROR =   'dynamicform.validation.error.file.upload.error';
}

?>