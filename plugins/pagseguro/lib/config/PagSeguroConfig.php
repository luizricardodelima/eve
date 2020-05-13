<?php

/*
 ************************************************************************
 PagSeguro Config File
 ************************************************************************
 */

$PagSeguroConfig = array();

$PagSeguroConfig['environment'] = "sandbox"; // production, sandbox

$PagSeguroConfig['credentials'] = array();
$PagSeguroConfig['credentials']['email'] = "secretaria@abragesp.org.br";
$PagSeguroConfig['credentials']['token']['production'] = "8A7179D3DA094DCDBBCE6D09990954C6";
$PagSeguroConfig['credentials']['token']['sandbox'] = "9AA7E65E7D6445D0B411AF1FE2E2F006";

$PagSeguroConfig['application'] = array();
$PagSeguroConfig['application']['charset'] = "UTF-8"; // UTF-8, ISO-8859-1

$PagSeguroConfig['log'] = array();
$PagSeguroConfig['log']['active'] = false;
$PagSeguroConfig['log']['fileLocation'] = "";
