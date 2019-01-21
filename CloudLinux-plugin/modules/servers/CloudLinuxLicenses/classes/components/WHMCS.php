<?php


namespace CloudLinuxLicenses\classes\components;


use CloudLinuxLicenses\classes\models\Admin;

class WHMCS extends Component
{
    /**
     * @var Admin
     */
    public $admin;

    /**
     * @param $command
     * @param $values
     * @return array
     * @throws \RuntimeException
     */
    public function execute($command, $values)
    {
        $admin = $this->getAdmin();
        $response = localAPI($command, $values, $admin->username);

        if ($response['result'] !== 'success') {
            logModuleCall('CloudLinux licenses Addon', $command,
                [$command, $admin->username, $values], json_encode($response), $response);
            throw new \RuntimeException($response['message']);
        }

        return $response;
    }

    public function moduleCreate($accountId)
    {
        $this->execute('modulecreate', [
            'accountid' => $accountId,
        ]);
    }

    public function moduleTerminate($accountId)
    {
        $this->execute('moduleterminate', [
            'accountid' => $accountId,
        ]);
    }

    public function moduleSuspend($accountId)
    {
        $this->execute('modulesuspend', [
            'accountid' => $accountId,
        ]);
    }

    public function moduleUnSuspend($accountId)
    {
        $this->execute('moduleunsuspend', [
            'accountid' => $accountId,
        ]);
    }

    /**
     * @return mixed
     */
    public function getAdmin()
    {
        if (!$this->admin) {
            $this->admin = Admin::getAdmin();
        }

        return $this->admin;
    }
}