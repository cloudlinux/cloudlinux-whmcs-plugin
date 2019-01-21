<?php

namespace CloudLinuxLicenses\classes\components;

use Illuminate\Database\Eloquent\Model;

class Addon extends Component {

    /**
     * @param string $action
     * @return array
     */
    public function execute($action)
    {
        try {
            if (!method_exists($this, $action)) {
                throw new \InvalidArgumentException(sprintf('Method %s doesn\'t exist', $action));
            }

            $this->{$action}();

            return [
                'status' =>  'success',
                'description' => 'Addon module activated',
            ];
        } catch (\Exception $e) {
            return [
                'status' =>  'error',
                'description' => 'Addon module FAILED to activate: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @throws \Exception
     */
    public function activate()
    {
        try {
            $schema = Model::resolveConnection()->getSchemaBuilder();
            $this->dropTables();
            
            $schema->create('CloudLinux_AddonRelations', function ($table) {
                /* @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->integer('addonID');
                $table->integer('licenseProductID');
            });

            $schema->create('CloudLinux_FreeProductRelations', function ($table) {
                /* @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->integer('freeProductID');
                $table->integer('licenseProductID');
            });

            $schema->create('CloudLinux_Connections', function ($table) {
                /* @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->integer('order_id');
                $table->integer('hosting_id');
                $table->integer('hostingb_id');
                $table->integer('user_id');
                $table->integer('product_id');
                $table->integer('relation_id');

                $table->index('relation_id');
                $table->index('hostingb_id');
                $table->index(['order_id','hosting_id', 'user_id', 'product_id'], 'order_id');
            });

            $schema->create('CloudLinux_ConfigurableOptionsRelations', function ($table) {
                /* @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->integer('product_id');
                $table->integer('option_group_id');
                $table->integer('option_id');
            });
        } catch (\Exception $e) {
            $this->dropTables();
            throw $e;
        }
    }

    /**
     *
     */
    public function deactivate()
    {
        $this->dropTables();
    }

    /**
     *
     */
    private function dropTables()
    {
        $schema = Model::resolveConnection()->getSchemaBuilder();
        $schema->dropIfExists('CloudLinux_AddonRelations');
        $schema->dropIfExists('CloudLinux_FreeProductRelations');
        $schema->dropIfExists('CloudLinux_Connections');
        $schema->dropIfExists('CloudLinux_ConfigurableOptionsRelations');
    }
}