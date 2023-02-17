<?php
namespace Coa\MessengerBundle\Messenger;

interface SettingInterface{
    public function getToken(): string;
    public function getId(): string;
}