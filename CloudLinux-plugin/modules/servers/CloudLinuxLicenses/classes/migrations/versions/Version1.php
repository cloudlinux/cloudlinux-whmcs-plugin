<?php

namespace CloudLinuxLicenses\classes\migrations\versions;

use CloudLinuxLicenses\classes\migrations\VersionInterface;

class Version1 implements VersionInterface
{
    /**
     * @return array
     */
    public function up()
    {
        return array(
            'CREATE TABLE IF NOT EXISTS `CloudLinux_ConfigurableOptionsRelations` (
                id INT AUTO_INCREMENT,
                product_id INT,
                option_group_id INT,
                option_id INT,
                PRIMARY KEY (id),
                INDEX (product_id)
            ) ENGINE=INNODB',
        );
    }

    public function down()
    {
        return array();
    }
}
