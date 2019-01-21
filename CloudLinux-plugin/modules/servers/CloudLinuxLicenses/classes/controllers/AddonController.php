<?php


namespace CloudLinuxLicenses\classes\controllers;


use CloudLinuxLicenses\classes\components\Controller;
use CloudLinuxLicenses\classes\components\Csrf;
use CloudLinuxLicenses\classes\models\Addon;
use CloudLinuxLicenses\classes\models\CLAddonRelations;
use CloudLinuxLicenses\classes\models\CLFreeProductRelations;
use CloudLinuxLicenses\classes\models\ConfigOptionGroup;
use CloudLinuxLicenses\classes\models\Package;
use CloudLinuxLicenses\classes\models\CLConfigurableOptions;
use Illuminate\Database\Eloquent\Model;


class AddonController extends Controller
{
    public function index()
    {
        $this->render('addon/index', [
            'csrfToken' => Csrf::get(),
        ]);
    }

    // Configurable option relations
    public function getOptionRelationsList(\stdClass $params) {
        $query = CLConfigurableOptions::with([
            'package' => function($q) {
                $q->select('id', 'name');
            },
            'option' => function($q) {
                $q->select('id', 'optionname AS name');
            },
            'optionGroup' => function($q) {
                $q->select('id', 'optionname AS name');
            }
        ]);

        $totalCount = $query->count();
        $query = $this->setOrder($query, [], $params, 'id');
        $query = $this->setPagination($query, $params);

        return [
            'data' => [
                'totalCount' => $totalCount,
                'items' => $query->get(),
            ],
        ];
    }

    public function addOptionRelation(\stdClass $params) {
        if (!$params->product_id) {
            throw new \InvalidArgumentException('License Product is required');
        }
        if (!$params->option_group_id) {
            throw new \InvalidArgumentException('Configurable Option group is required');
        }
        if (!$params->option_id) {
            throw new \InvalidArgumentException('Configurable Option is required');
        }

        return [
            'data' => CLConfigurableOptions::firstOrCreate((array) $params),
            'message' => 'Relation has been added successfully',
        ];
    }

    public function editOptionRelation(\stdClass $params) {
        if (!$params->product_id) {
            throw new \InvalidArgumentException('License Product is required');
        }
        if (!$params->option_group_id) {
            throw new \InvalidArgumentException('Configurable Option group is required');
        }
        if (!$params->option_id) {
            throw new \InvalidArgumentException('Configurable Option is required');
        }

        CLConfigurableOptions::find($params->id)->update((array) $params);

        return [
            'message' => 'Relation has been updated successfully',
        ];
    }

    public function deleteOptionRelation(\stdClass $params) {
        $model = CLConfigurableOptions::find($params->id);
        if (!$model) {
            throw new \InvalidArgumentException('Relation not found');
        }
        $model->delete();

        return [
            'message' => 'Relation has been deleted successfully',
        ];
    }

    // Product relations
    public function getProductRelationsList(\stdClass $params) {
        $query = CLFreeProductRelations::with([
            'package' => function($q) {
                $q->select('id', 'name');
            },
            'nonClPackage' => function($q) {
                $q->select('id', 'name');
            }
        ]);

        $totalCount = $query->count();
        $query = $this->setOrder($query, [], $params, 'id');
        $query = $this->setPagination($query, $params);

        return [
            'data' => [
                'totalCount' => $totalCount,
                'items' => $query->get(),
            ],
        ];
    }

    public function addProductRelation(\stdClass $params) {
        if (!$params->non_cl_product_id) {
            throw new \InvalidArgumentException('Main product is required');
        }
        if (!$params->product_id) {
            throw new \InvalidArgumentException('Product is required');
        }

        return [
            'data' => CLFreeProductRelations::firstOrCreate([
                'freeProductID' => $params->non_cl_product_id,
                'licenseProductID' => $params->product_id,
            ]),
            'message' => 'Relation has been added successfully',
        ];
    }

    public function editProductRelation(\stdClass $params) {
        if (!$params->freeProductID) {
            throw new \InvalidArgumentException('Main product is required');
        }
        if (!$params->licenseProductID) {
            throw new \InvalidArgumentException('Product is required');
        }
        CLFreeProductRelations::find($params->id)->update((array) $params);

        return [
            'message' => 'Relation has been updated successfully',
        ];
    }

    public function deleteProductRelation(\stdClass $params) {
        $model = CLFreeProductRelations::find($params->id);
        if (!$model) {
            throw new \InvalidArgumentException('Relation not found');
        }
        $model->delete();

        return [
            'message' => 'Relation has been deleted successfully',
        ];
    }

    // Addon relations
    public function getAddonRelationsList(\stdClass $params) {
        $query = CLAddonRelations::with([
            'package' => function($q) {
                $q->select('id', 'name');
            },
            'addon' => function($q) {
                $q->select('id', 'name');
            }
        ]);

        $totalCount = $query->count();
        $query = $this->setOrder($query, [], $params, 'id');
        $query = $this->setPagination($query, $params);

        return [
            'data' => [
                'totalCount' => $totalCount,
                'items' => $query->get(),
            ],
        ];
    }

    public function addAddonRelation(\stdClass $params) {
        if (!$params->addon_id) {
            throw new \InvalidArgumentException('Addon is required');
        }
        if (!$params->product_id) {
            throw new \InvalidArgumentException('Product is required');
        }

        return [
            'data' => CLAddonRelations::firstOrCreate([
                'addonID' => $params->addon_id,
                'licenseProductID' => $params->product_id,
            ]),
            'message' => 'Relation has been added successfully',
        ];
    }

    public function editAddonRelation(\stdClass $params) {
        if (!$params->addonID) {
            throw new \InvalidArgumentException('Addon is required');
        }
        if (!$params->licenseProductID) {
            throw new \InvalidArgumentException('Product is required');
        }
        CLAddonRelations::find($params->id)->update((array) $params);

        return [
            'message' => 'Relation has been updated successfully',
        ];
    }

    public function deleteAddonRelation(\stdClass $params) {
        $model = CLAddonRelations::find($params->id);
        if (!$model) {
            throw new \InvalidArgumentException('Relation not found');
        }
        $model->delete();

        return [
            'message' => 'Relation has been deleted successfully',
        ];
    }

    public function getFieldsData(\stdClass $params) {
        $data = [
            'nonClProducts' => Package::typeNotCL()->orderBy('name', 'asc')->get(),
            'clProducts' => Package::typeCL()->orderBy('name', 'asc')->get(),
            'allProducts' => Package::orderBy('name', 'asc')->get(),
            'addons' => Addon::orderBy('name', 'asc')->get(),
        ];

        if (isset($params->withOptions)) {
            $data['optionGroups'] = ConfigOptionGroup::select('id', 'optionname AS name')
                ->with([
                    'options' => function($q) {
                        $q->select('id', 'configid', 'optionname AS name');
                    }
                ])->orderBy('optionname', 'desc')->get();
        }

        return [
            'data' => $data,
        ];
    }

    public function getLicenseList(\stdClass $params) {
        $db = Model::resolveConnection();
        $query = $db->table('CloudLinux_Connections AS l')
            ->select('l.id AS id', 'c.id as user_id', $db->raw('CONCAT_WS(\' \', c.firstname, c.lastname) AS client_name'),
                'p.id AS main_product_id', 'p.name AS main_product_name', 'h.id AS hosting_id',
                'lh.id AS license_id', 'pl.name AS license_name', 'pl.configoption3 as license_type',
                $db->raw('CONCAT_WS(\' \', lh.dedicatedip, cv.value) AS license_item'))
            ->leftJoin('tblproducts AS pl', $db->raw('pl.id'), '=', $db->raw('l.product_id'))
            ->leftJoin('tblhosting AS h', $db->raw('h.id'), '=', $db->raw('l.hosting_id'))
            ->leftJoin('tblproducts AS p', $db->raw('p.id'), '=', $db->raw('h.packageid'))
            ->leftJoin('tblclients AS c', $db->raw('c.id'), '=', $db->raw('l.user_id'))
            ->leftJoin('tblcustomfieldsvalues AS cv', $db->raw('cv.relid'), '=', $db->raw('l.hostingb_id'))
            ->leftJoin('tblhosting AS lh', $db->raw('lh.id'), '=', $db->raw('l.hostingb_id'))
            ->where(function ($q) {
                $q->whereNull('cv.value')->orWhere('cv.value', '!=', '');
            });

        $optionsQuery = $db->table('tblhostingconfigoptions AS hco')
            ->select('hco.id AS id', 'c.id as user_id', $db->raw('CONCAT_WS(\' \', c.firstname, c.lastname) AS client_name'),
                'mp.id AS main_product_id', 'mp.name AS main_product_name', 'h.id AS hosting_id',
                'h.id AS license_id', 'p.name AS license_name', 'p.configoption3 as license_type',
                $db->raw('NULL AS license_item'))
            ->leftJoin('tblhosting AS h', $db->raw('h.id'), '=', $db->raw('hco.relid'))
            ->rightJoin('CloudLinux_ConfigurableOptionsRelations AS clo',
                $db->raw('clo.option_id'), '=', $db->raw('hco.optionid'))
            ->leftJoin('tblproducts AS p', $db->raw('p.id'), '=', $db->raw('clo.product_id'))
            ->leftJoin('tblproducts AS mp', $db->raw('mp.id'), '=', $db->raw('h.packageid'))
            ->leftJoin('tblclients AS c', $db->raw('c.id'), '=', $db->raw('h.userid'))
            ->whereNotNull('h.id');

        $filterAttributes = [
            'client_name' => function ($query, $value) {
                if (strpos($value, ' ') !== false) {
                    $name = explode(' ', $value);
                    $query->where('c.firstname', $name[0]);
                    $query->where('c.lastname', $name[1]);
                } else {
                    $query->where('c.firstname', $value);
                }
                return $query;
            },
            'main_product' => 'p.id',
            'license_product' => 'pl.id',
            'license_ip' => 'lh.dedicatedip',
            'license_key' => 'cv.value',
        ];
        $optionsFilterAttributes = [
            'client_name' => function ($query, $value) {
                if (strpos($value, ' ') !== false) {
                    $name = explode(' ', $value);
                    $query->where('c.firstname', $name[0]);
                    $query->where('c.lastname', $name[1]);
                } else {
                    $query->where('c.firstname', $value);
                }
                return $query;
            },
            'main_product' => 'mp.id',
            'license_product' => 'p.id',
        ];

        $query = $this->setFilters($query, $filterAttributes, $params);
        $optionsQuery = $this->setFilters($optionsQuery, $optionsFilterAttributes, $params);

        $sortAttributes = [
            'id' => 'l.id',
            'user_id' => 'c.id',
            'client_name' => 'c.lastname',
            'main_product' => 'p.name',
            'license_product' => 'pl.name',
            'license_type' => 'pl.configoption3',
        ];
        $query = $this->setOrder($query, $sortAttributes, $params, 'l.id');

        $totalCount = $query->count() + $optionsQuery->count();
        $query = $query->union($optionsQuery);
        $query = $this->setPagination($query, $params);
        $relations = $query->get();

        return [
            'data' => [
                'totalCount' => $totalCount,
                'items' => $relations,
            ],
        ];
    }

    public function getAddonList(\stdClass $params) {
        $db = Model::resolveConnection();
        $query = $db->table('tblhostingaddons AS hostingaddons')
            ->select('hostingaddons.id', 'clients.id as user_id',
                $db->raw('CONCAT_WS(\' \', clients.firstname, clients.lastname) AS client_name'),
                'addons.name AS addon_name', 'hostingaddons.hostingid AS hosting_id',
                'customfieldvalues.value AS license_item', 'configuration.value AS license_type')
            ->leftJoin('tbladdons AS addons', $db->raw('addons.id'), '=', $db->raw('hostingaddons.addonid'))
            ->leftJoin('tblclients AS clients', $db->raw('clients.id'), '=', $db->raw('hostingaddons.userid'))
            ->leftJoin('tblcustomfieldsvalues AS customfieldvalues', $db->raw('customfieldvalues.relid'), '=', $db->raw('hostingaddons.id'))
            ->leftJoin('tblmodule_configuration AS configuration', $db->raw('configuration.entity_id'), '=', $db->raw('addons.id'))
            ->whereRaw('customfieldvalues.fieldid IN
             (SELECT id FROM tblcustomfields where type = "addon" AND relid = addons.id AND fieldname LIKE "License%")')
            ->where('hostingaddons.status', 'Active')
            ->where('addons.module', 'CloudLinuxLicenses')
            ->where('configuration.entity_type', 'addon')
            ->where('configuration.setting_name', 'configoption3');

        $filterAttributes = [
            'client_name' => function ($query, $value) {
                if (strpos($value, ' ') !== false) {
                    $name = explode(' ', $value);
                    $query->where('clients.firstname', $name[0]);
                    $query->where('clients.lastname', $name[1]);
                } else {
                    $query->where('clients.firstname', $value);
                }
                return $query;
            },
            'addon_id' => 'addons.id',
            'license_item' => 'customfieldvalues.value',
        ];
        $query = $this->setFilters($query, $filterAttributes, $params);

        $sortAttributes = [
            'id' => 'hostingaddons.id',
            'user_id' => 'clients.id',
            'client_name' => 'clients.lastname',
            'addon_name' => 'addons.name',
            'license_item' => 'customfieldvalues.value',
            'license_type' => 'configuration.value',
        ];
        $query = $this->setOrder($query, $sortAttributes, $params, 'l.id');

        $totalCount = $query->count();
        $query = $this->setPagination($query, $params);
        $relations = $query->get();

        return [
            'data' => [
                'totalCount' => $totalCount,
                'items' => $relations,
            ],
        ];
    }
}