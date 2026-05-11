<?php
namespace app\miniapp\service;

use app\model\WxConfig;

class HomeService
{
    public function getHomeData(): array
    {
        return [
            'banners' => $this->getBanners(),
            'grid_nav' => $this->getGridNav(),
            'notices' => $this->getNotices(),
            'recommend' => $this->getRecommend(),
        ];
    }

    private function getBanners(): array
    {
        $bannersJson = WxConfig::getValue('home_banners', '[]');
        return json_decode($bannersJson, true) ?: [];
    }

    private function getGridNav(): array
    {
        $gridNavJson = WxConfig::getValue('home_grid_nav', '[]');
        return json_decode($gridNavJson, true) ?: [];
    }

    private function getNotices(): array
    {
        $noticesJson = WxConfig::getValue('home_notices', '[]');
        return json_decode($noticesJson, true) ?: [];
    }

    private function getRecommend(): array
    {
        $recommendJson = WxConfig::getValue('home_recommend', '[]');
        return json_decode($recommendJson, true) ?: [];
    }
}
