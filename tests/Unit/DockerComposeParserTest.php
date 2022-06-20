<?php

use App\EzRider\DockerComposeParser;

const DOCKER_COMPOSE_TEST_PATH = '/tests/Data/docker-compose-test.yml';

test('It can successfully load Docker Compose Files', function () {
    $parser = resolve(DockerComposeParser::class);
    $dockerConfig = $parser->loadDockerComposeFile(getcwd() . DOCKER_COMPOSE_TEST_PATH);
    expect($dockerConfig['version'])->toEqual('3.3');
    expect($dockerConfig['services']['test-service']['container_name'])->toEqual('test-service');
});

test('It returns services with environment variables', function () {
    $parser = resolve(DockerComposeParser::class);
    $dockerConfig = $parser->loadDockerComposeFile(getcwd() . DOCKER_COMPOSE_TEST_PATH);
    $servicesWithEnvironmentVariables = $parser->getEnvironmentVariablesByService($dockerConfig);
    expect(count($servicesWithEnvironmentVariables))->toEqual(2);
});

test('It parses environment variables', function () {
    $parser = resolve(DockerComposeParser::class);
    $dockerConfig = $parser->loadDockerComposeFile(getcwd() . DOCKER_COMPOSE_TEST_PATH);
    $servicesWithEnvironmentVariables = $parser->getEnvironmentVariablesByService($dockerConfig);
    $environmentVariablesServiceA = $parser->mapEnvironmentVariablesFromServiceData($servicesWithEnvironmentVariables['test-mysql-service'], 'test-mysql-service');
    $environmentVariablesServiceB = $parser->mapEnvironmentVariablesFromServiceData($servicesWithEnvironmentVariables['test-service'], 'test-service');
    expect(count($environmentVariablesServiceA))->toEqual(4);
    expect(count($environmentVariablesServiceB))->toEqual(12);
});
