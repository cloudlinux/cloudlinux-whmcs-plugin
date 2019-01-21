<?php

namespace CloudLinuxLicenses\classes\migrations;

interface VersionInterface
{
    public function up();
    public function down();
}