<?php


namespace CloudLinuxLicenses\classes\api\responseModels;


class Server extends AbstractResponseModel
{
    public function hostname()
    {
        $hostname = gethostbyaddr($this->ip);
        return ($hostname === $this->ip) ? '' : $hostname;
    }

    public function createdDate()
    {
        return $this->created ? $this->toDate($this->created) : '-';
    }

    public function checkinDate()
    {
        return $this->toDate($this->last_checkin);
    }
}