<?php
/*
 * @link https://github.com/panlatent/craft-aliyun
 * @copyright Copyright (c) 2024 panlatent@gmail.com
 */

namespace panlatent\craft\aliyun\controllers;

use Craft;
use craft\web\Controller;
use panlatent\craft\aliyun\models\Credential;
use panlatent\craft\aliyun\Plugin;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CredentialsController extends Controller
{

    /**
     * Edit a credential.
     *
     * @param int|null $credentialId
     * @param Credential|null $credential
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionEditCredential(int $credentialId = null, Credential $credential = null): Response
    {
        if ($credential === null) {
            if ($credentialId !== null) {
                $credential = Plugin::$plugin->credentials->getCredentialById($credentialId);
                if (!$credential) {
                    throw new NotFoundHttpException();
                }
            } else {
                $credential = new Credential();
            }
        }

        return $this->renderTemplate('aliyun/settings/credentials/_edit', [
            'credential' => $credential,
            'isNewCredential' => $credential->id === null,
        ]);
    }

    public function actionSaveCredential(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $credential = new Credential([
            'id' => $request->getBodyParam('credentialId'),
            'name' => $request->getBodyParam('name'),
            'handle' => $request->getBodyParam('handle'),
            'accessKeyId' => $request->getBodyParam('accessKeyId'),
            'accessKeySecret' => $request->getBodyParam('accessKeySecret'),
        ]);

        if (!Plugin::$plugin->credentials->saveCredential($credential)) {
            Craft::$app->getSession()->setError(Craft::t('aliyun', 'Couldn’t save credential.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'credential' => $credential,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('aliyun', 'Credential saved.'));

        return $this->redirect('aliyun/settings/credentials');
    }

    public function actionDeleteCredential(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $credentialId = Craft::$app->getRequest()->getBodyParam('id');
        $credential = Plugin::$plugin->credentials->getCredentialById($credentialId);
        if (!$credential) {
            throw new NotFoundHttpException();
        }

        try {
            Plugin::$plugin->credentials->deleteCredential($credential);
        } catch (\Throwable $e) {
            return $this->asJson(['success' => false]);
        }

        return $this->asJson(['success' => true]);
    }
}