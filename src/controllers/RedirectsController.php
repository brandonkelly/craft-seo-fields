<?php

namespace studioespresso\seofields\controllers;

use Craft;
use craft\helpers\App;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use League\Csv\Reader;
use studioespresso\seofields\models\RedirectModel;
use studioespresso\seofields\models\SeoDefaultsModel;
use studioespresso\seofields\records\DefaultsRecord;
use studioespresso\seofields\SeoFields;
use yii\helpers\StringHelper;
use yii\web\UploadedFile;

class RedirectsController extends Controller
{
    const IMPORT_FILE = 'seofields_redirects_import.csv';

    public function actionIndex()
    {
        $searchParam = Craft::$app->getRequest()->getParam('search');
        $redirects = SeoFields::getInstance()->redirectService->getAllRedirects($searchParam);
        return $this->renderTemplate('seo-fields/_redirect/_index', ['redirects' => $redirects]);
    }

    public function actionAdd()
    {
        return $this->renderTemplate('seo-fields/_redirect/_entry', [
            'pattern' => Craft::$app->getRequest()->getParam('pattern') ?? null,
            'sites' => $this->getSitesMenu()
        ]);
    }

    public function actionEdit($id)
    {
        $redirect = SeoFields::getInstance()->redirectService->getRedirectById($id);
        return $this->renderTemplate('seo-fields/_redirect/_entry', [
            'data' => $redirect,
            'sites' => $this->getSitesMenu()
        ]);
    }

    public function actionSave()
    {
        $id = Craft::$app->getRequest()->getBodyParam('redirectId');
        if ($id) {
            $model = SeoFields::getInstance()->redirectService->getRedirectById($id);
        } else {
            $model = new RedirectModel();
        }

        $model->setAttributes(Craft::$app->getRequest()->getBodyParam('fields'));

        if ($model->validate()) {
            $saved = SeoFields::getInstance()->redirectService->saveRedirect($model);
            if ($saved) {
                Craft::$app->getSession()->setNotice(Craft::t('seo-fields', 'Redirect saved'));
                $this->redirectToPostedUrl();
            }
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save redirect.'));
        return $this->renderTemplate('seo-fields/_redirect/_entry', [
            'data' => $model,
            'sites' => $this->getSitesMenu()
        ]);

    }

    public function actionUpload()
    {
        $this->requirePostRequest();

        // If your CSV document was created or is read on a Macintosh computer,
        // add the following lines before using the library to help PHP detect line ending in Mac OS X
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $file = UploadedFile::getInstanceByName('file');

        if ($file !== null) {
            $filename = self::IMPORT_FILE;
            $filePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;
            $file->saveAs($filePath, false);
            $csv = Reader::createFromPath($file->tempName);
            $headers = $csv->fetchOne(0);
            Craft::info(print_r($headers, true), __METHOD__);
            $variables['headers'] = $headers;
            $variables['filename'] = $filePath;
        }

        $this->redirect(UrlHelper::cpUrl('seo-fields/redirects/import'));
    }

    public function actionImport()
    {

        $filename = self::IMPORT_FILE;
        $filePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;
        if(!file_exists($filePath)) {
            return $this->redirect(UrlHelper::cpUrl('seo-fields/redirects'));
        }
        $csv = Reader::createFromPath($filePath);
        $headers = $csv->fetchOne(0);
        $variables['headers'] = $headers;
        $variables['filename'] = $filePath;

        $this->renderTemplate('seo-fields/_redirect/_import', $variables);
    }

    public function actionRunImport()
    {
        $request = Craft::$app->getRequest();
        $data = $request->getBodyParam('fields');
        if (!$data['pattern'] || $data['redirect'] || $data['method']) {
        }

        App::maxPowerCaptain();
        $settings = [
            'patternCol' => $data['pattern'],
            'redirectCol' => $data['redirect'],
            'method' => $data['method'],
        ];

        $filename = self::IMPORT_FILE;
        $filePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $filename;
        $csv = Reader::createFromPath($filePath);
        $headers = $csv->fetchOne(0);
        $variables['headers'] = $headers;
        $variables['filename'] = $filePath;

        $csv->setOffset(1);
        $results = SeoFields::getInstance()->redirectService->import($csv->fetchAll(), $settings);
        return $this->renderTemplate('seo-fields/_redirect/_import_results', $results);

    }

    public function actionDelete($id)
    {
        if (SeoFields::getInstance()->redirectService->deleteRedirectById($id)) {
            Craft::$app->getSession()->setNotice(Craft::t('seo-fields', 'Redirect removed'));
            $this->redirect(UrlHelper::cpUrl('seo-fields/redirects'));
        }
    }

    private function getSitesMenu()
    {
        $sites = [
            0 => Craft::t('seo-fields', 'All Sites'),
        ];

        if (Craft::$app->getIsMultiSite()) {
            $editableSites = Craft::$app->getSites()->getEditableSiteIds();
            foreach (Craft::$app->getSites()->getAllGroups() as $group) {
                $groupSites = Craft::$app->getSites()->getSitesByGroupId($group->id);
                $sites[$group->name]
                    = ['optgroup' => $group->name];
                foreach ($groupSites as $groupSite) {
                    if (in_array($groupSite->id, $editableSites, false)) {
                        $sites[$groupSite->id] = $groupSite->name;
                    }
                }
            }
        }
        return $sites;
    }
}
