<?php

namespace PHPTerminalModulesFirewall;

use Carbon\Carbon;
use PHPFirewall\Firewall as PHPFirewallFirewall;
use PHPTerminal\Modules;
use PHPTerminal\Terminal;

class Firewall extends Modules
{
    protected $terminal;

    protected $command;

    protected $firewallPackage;

    protected $firewallConfig;

    protected $auth;

    public function init(Terminal $terminal = null, $command) : object
    {
        $this->terminal = $terminal;

        $this->command = $command;

        $this->firewallPackage = new PHPFirewallFirewall;

        $this->getFirewallConfig();

        return $this;
    }

    protected function getFirewallConfig()
    {
        $firewall = $this->firewallPackage->getFirewallConfig();

        if (isset($firewall['response']['responseCode']) && $firewall['response']['responseCode'] == 0) {
            $this->firewallConfig = $firewall['response']['responseData'];
        }
    }

    public function getCommands() : array
    {
        $commands =
            [
                [
                    "availableAt"   => "enable",
                    "command"       => "",
                    "description"   => "show commands",
                    "function"      => ""
                ],
                [
                    "availableAt"   => "enable",
                    "command"       => "show firewall",
                    "description"   => "Show firewall modules.",
                    "function"      => "show"
                ],
                [
                    "availableAt"   => "enable",
                    "command"       => "show filters",
                    "description"   => "Show firewall filters",
                    "function"      => "show"
                ],
                [
                    "availableAt"   => "enable",
                    "command"       => "show filter",
                    "description"   => "show filter {address}",
                    "function"      => "show"
                ],
                [
                    "availableAt"   => "enable",
                    "command"       => "",
                    "description"   => "",
                    "function"      => ""
                ],
                [
                    "availableAt"   => "enable",
                    "command"       => "firewall check ip",
                    "description"   => "firewall check ip {address}. Check state of an ip address, if it is being blocked/allowed/monitored by the firewall.",
                    "function"      => "firewall"
                ],
            ];

        //Set Commands
        array_push($commands,
            [
                "availableAt"   => "config",
                "command"       => "",
                "description"   => "firewall set commands",
                "function"      => ""
            ],
            [
                "availableAt"   => "config",
                "command"       => "firewall set status",
                "description"   => "firewall set status {status}. Status options: enable/disable/monitor.",
                "function"      => "firewall"
            ],
            [
                "availableAt"   => "config",
                "command"       => "firewall set filter",
                "description"   => "firewall set filter {ip_type} {status}. Ip_type options: v4/v6, Status options: enable/disable.",
                "function"      => "firewall"
            ],
            [
                "availableAt"   => "config",
                "command"       => "firewall set range",
                "description"   => "firewall set range {range_type} {status}. Range_type options: private/reserved, Status options: enable/disable.",
                "function"      => "firewall"
            ],
            [
                "availableAt"   => "config",
                "command"       => "firewall set default filter",
                "description"   => "firewall set default filter {state}. Status options: allow/block.",
                "function"      => "firewall"
            ],
            [
                "availableAt"   => "config",
                "command"       => "firewall reset default filter hit count",
                "description"   => "firewall reset default filter hit count. Resets default filter hit counts to 0.",
                "function"      => "firewall"
            ],
            [
                "availableAt"   => "config",
                "command"       => "firewall set auto unblock",
                "description"   => "firewall set auto unblock {minutes}. After X minutes firewall will auto unblock IP from block state. Disable the feature by setting minutes to 0.",
                "function"      => "firewall"
            ],
            [
                "availableAt"   => "config",
                "command"       => "firewall set ip2location key",
                "description"   => "firewall set ip2location key {key}. Set key as null to remove the key",
                "function"      => "firewall"
            ]
        );

        //Filter Commands
        array_push($commands,
            [
                "availableAt"   => "config",
                "command"       => "",
                "description"   => "",
                "function"      => ""
            ],
            [
                "availableAt"   => "config",
                "command"       => "",
                "description"   => "filter commands",
                "function"      => ""
            ],
            [
                "availableAt"   => "config",
                "command"       => "filter add",
                "description"   => "filter add {filter_type} {address_type} {ip_address|network/subnet|region/{country|state|city}}. filter_type options: allow, block, monitor. address_type options: host, network, region.",
                "function"      => "filter"
            ],
            [
                "availableAt"   => "config",
                "command"       => "filter update",
                "description"   => "filter update {filter_id} {filter_type}. Update filter type. filter_type options: allow, block, monitor.",
                "function"      => "filter"
            ],
            [
                "availableAt"   => "config",
                "command"       => "filter remove",
                "description"   => "filter remove {filter_id}. Remove a filter",
                "function"      => "filter"
            ]
        );

        return $commands;
    }

    protected function showFirewall()
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        $this->getFirewallConfig();

        $this->terminal->addResponse('', 0, ['Firewall Details' => $this->firewallConfig]);

        return true;
    }

    protected function showFilters()
    {
        $filters = $this->firewallPackage->getFilters();

        if ($filters === true) {
            $filters = $this->firewallPackage->response->getAllData();
        }

        if (isset($filters['response']['responseCode']) && $filters['response']['responseCode'] == 0) {
            if (isset($filters['response']['responseData']['filters']) && count($filters['response']['responseData']['filters']) > 0) {
                if (isset($this->terminal->config['plugins']['auth']['class'])) {
                    $this->auth = (new $this->terminal->config['plugins']['auth']['class']())->init($this->terminal);
                }

                array_walk($filters['response']['responseData']['filters'], function(&$filter) {
                    if (isset($filter['updated_by']) && $filter['updated_by'] != 0) {
                        $account = $this->auth->getAccountById($filter['updated_by']);

                        if (isset($account['profile']['full_name']) || isset($account['profile']['email'])) {
                            $filter['updated_by'] = $account['profile']['full_name'] ?? $account['profile']['email'];
                        }
                    }
                    if (isset($filter['updated_at'])) {
                        $time = Carbon::parse($filter['updated_at']);
                        $filter['updated_at'] = $time->toDateTimeString();
                    }
                });

                $this->terminal->addResponse(
                    '',
                    0,
                    ['Firewall Details' => $filters['response']['responseData']['filters']],
                    true,
                    [
                        '_id', 'filter_type', 'address_type', 'address', 'hit_count', 'updated_by', 'updated_at'
                    ],
                    [
                        5,15,15,50,15,25,25
                    ]
                );
            } else  {
                $this->terminal->addResponse('Firewall has no filters!', 2);
            }

            return true;
        }

        $this->terminal->addResponse('Error retrieving firewall filters!', 1);

        return false;
    }

    protected function showFilter($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        //Address
        if (!isset($args[0])) {
            $this->terminal->addResponse('Please enter correct address', 1);

            return false;
        }

        $filter = $this->firewallPackage->getFilterByAddress($args[0], true);

        if ($filter) {
            $this->terminal->addResponse('Filter added successfully', 0, ['filter' => $filter]);

            return true;
        }

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    protected function firewallSetStatus($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        if (!isset($args[0])) {
            $this->terminal->addResponse('Please provide status', 1);

            return false;
        }

        $firewallConfig = $this->firewallPackage->setConfigStatus($args[0]);

        if ($firewallConfig) {
            $this->showFirewall();

            return true;
        }

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    protected function firewallSetFilter($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        if (!isset($args[0])) {
            $this->terminal->addResponse('Please provide filter ip type. Options: v4/v6', 1);

            return false;
        }

        if (!isset($args[1])) {
            $this->terminal->addResponse('Please provide filter status. Options enable/disable', 1);

            return false;
        }

        $firewallConfig = $this->firewallPackage->setConfigFilter($args[0], $args[1]);

        if ($firewallConfig) {
            $this->showFirewall();

            return true;
        }

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    protected function firewallSetRange($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        if (!isset($args[0])) {
            $this->terminal->addResponse('Please provide filter range type. Options: private/reserved', 1);

            return false;
        }

        if (!isset($args[1])) {
            $this->terminal->addResponse('Please provide filter status. Options enable/disable', 1);

            return false;
        }

        $firewallConfig = $this->firewallPackage->setConfigRange($args[0], $args[1]);

        if ($firewallConfig) {
            $this->showFirewall();

            return true;
        }

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    protected function firewallSetDefaultFilter($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        if (!isset($args[0])) {
            $this->terminal->addResponse('Please provide default filter state. Options: allow/block', 1);

            return false;
        }

        $firewallConfig = $this->firewallPackage->setConfigDefaultFilter($args[0]);

        if ($firewallConfig) {
            $this->showFirewall();

            return true;
        }

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    protected function firewallSetAutoUnblock($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        if (!isset($args[0])) {
            $this->terminal->addResponse('Please provide correct minutes.', 1);

            return false;
        }

        $firewallConfig = $this->firewallPackage->setConfigAutoUnblockIpMinutes((int) $args[0]);

        if ($firewallConfig) {
            $this->showFirewall();

            return true;
        }

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    protected function firewallSetIp2locationKey($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        if (!isset($args[0])) {
            $this->terminal->addResponse('Please provide correct key', 1);

            return false;
        }

        $firewallConfig = $this->firewallPackage->setConfigIp2locationKey($args[0]);

        if ($firewallConfig) {
            $this->showFirewall();

            return true;
        }

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    protected function firewallResetDefaultFilterHitCount()
    {
        $resetConfirmationArr = $this->terminal->inputToArray(['confirm reset'], ['confirm reset' => 'yes/no']);

        if (strtolower($resetConfirmationArr['confirm reset']) === 'yes') {
            $firewallConfig = $this->firewallPackage->resetConfigDefaultFilterHitCount();

            if ($firewallConfig) {
                $this->showFirewall();

                return true;
            }
        } else if (strtolower($resetConfirmationArr['confirm reset']) === 'no') {
            return true;
        } else {
            $this->terminal->addResponse('Unknown option entered : ' . $resetConfirmationArr['confirm reset'], 1);

            return false;
        }

        return true;
    }

    protected function firewallCheckIp($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        if (!isset($args[0])) {
            $this->terminal->addResponse('Please provide correct ip address', 1);

            return false;
        }

        $filter = $this->firewallPackage->checkIp($args[0]);

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    protected function filterAdd($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        //Filter Type
        if (!isset($args[0])) {
            $this->terminal->addResponse('Please enter correct filter type', 1);

            return false;
        }

        if ($args[0] !== 'allow' && $args[0] !== 'block' && $args[0] !== 'monitor') {
            $this->terminal->addResponse('Please enter correct filter type. allow/block/monitor are available filter type options. Don\'t know what ' . $args[0] . ' is...', 1);

            return false;
        }

        //Address Type
        if (!isset($args[1])) {
            $this->terminal->addResponse('Please enter correct address type', 1);

            return false;
        }

        if ($args[1] !== 'host' && $args[1] !== 'network' && $args[1] !== 'region') {
            $this->terminal->addResponse('Please enter correct address type. host/network/region are available address type options. Don\'t know what ' . $args[1] . ' is...', 1);

            return false;
        }

        //Address/region
        if (!isset($args[2])) {
            $this->terminal->addResponse('Please enter correct address/region', 1);

            return false;
        }

        $filterData['filter_type'] = $args[0];
        $filterData['address_type'] = $args[1];
        $filterData['address'] = $args[2];
        $filterData['updated_by'] = $this->terminal->getAccount()['id'] ?? 0;
        $filterData['updated_at'] = time();
        $filterData['hit_count'] = 0;

        $newFilter = $this->firewallPackage->addFilter($filterData);

        if ($newFilter) {
            $this->terminal->addResponse('Filter added successfully', 0, ['newFilter' => $newFilter]);

            return true;
        }

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    protected function filterUpdate($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        if (!isset($args[0]) || !isset($args[1])) {
            $this->terminal->addResponse('Please provide filter ID and filter type', 1);

            return false;
        }

        //Filter Type
        if ($args[1] !== 'allow' && $args[1] !== 'block' && $args[1] !== 'monitor') {
            $this->terminal->addResponse('Please enter correct filter type. allow/block/monitor are available filter type options. Don\'t know what ' . $args[1] . ' is...', 1);

            return false;
        }

        $filterData['id'] = $args[0];
        $filterData['filter_type'] = $args[1];
        $filterData['updated_by'] = $this->terminal->getAccount()['id'] ?? 0;
        $filterData['updated_at'] = time();

        $updateFilter = $this->firewallPackage->updateFilter($filterData);

        if ($updateFilter) {
            $this->terminal->addResponse('Filter updated successfully', 0);

            return true;
        }

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    protected function filterRemove($args)
    {
        if (!$this->firewallConfig) {
            $this->terminal->addResponse('Error retrieving firewall details. Contact developer!', 1);

            return false;
        }

        if (!isset($args[0])) {
            $this->terminal->addResponse('Please provide filter ID', 1);

            return false;
        }

        $filterData['id'] = $args[0];

        $updateFilter = $this->firewallPackage->removeFilter($filterData);

        if ($updateFilter) {
            $this->terminal->addResponse('Filter removed successfully', 0);

            return true;
        }

        $this->addFirewallResponseToTerminalResponse();

        return true;
    }

    public function onInstall() : object
    {
        $this->terminal->config['modules']['firewall']['banner'] =
            'PHPTerminal-modules-firewall is an firewall module for PHPTerminal to manage PHPFirewall library.';

        return $this;
    }

    protected function addFirewallResponseToTerminalResponse()
    {
        $response = $this->firewallPackage->response->getAllData();

        if (isset($response['response']['responseCode']) && isset($response['response']['responseMessage'])) {
            $this->terminal->addResponse(
                $response['response']['responseMessage'],
                $response['response']['responseCode'],
                $response['response']['responseData'] ?? []
            );
        }
    }
}