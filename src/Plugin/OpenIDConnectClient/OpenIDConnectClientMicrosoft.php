<?php

/**
 * @file
 * Contains \Drupal\openid_connect\Plugin\OpenIDConnectClient\OpenIDConnectClientMicrosoft.
 * Implements OpenID Connect Client plugin for Microsoft services, including Microsoft Account, Azure Active Directory, and Office 365.
 *
 */

namespace Drupal\openid_connect\Plugin\OpenIDConnectClient;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Drupal\openid_connect\StateToken;

/**
 * OpenID Connect client for Microsoft.
 *
 * @OpenIDConnectClient(
 *   id = "microsoft",
 *   label = @Translation("Microsoft")
 * )
 */
class OpenIDConnectClientMicrosoft extends OpenIDConnectClientBase {

  /**
   * Overrides OpenIDConnectClientBase::getEndpoints().
   */
  public function getEndpoints() {
    return array(
      'authorization' => 'https://login.microsoftonline.com/common/oauth2/authorize',
      'token' => 'https://login.microsoftonline.com/common/oauth2/token',
      'userinfo' => 'https://login.microsoftonline.com/common/openid/userinfo',
    );
  }

  /**
   * Overrides OpenIDConnectClientBase::authorize().
   */
  public function authorize($scope = 'openid email') {
    $redirect_uri = Url::fromRoute(
      'openid_connect.redirect_controller_redirect',
      array('client_name' => $this->pluginId), array('absolute' => TRUE)
    )->toString();

    $url_options = array(
      'query' => array(
        'client_id' => $this->configuration['client_id'],
        'response_type' => 'code',
        'scope' => $scope,
        'redirect_uri' => $redirect_uri,
        'state' => StateToken::create(),
      ),
    );

    $endpoints = $this->getEndpoints();
    // Clear _GET['destination'] because we need to override it.
    $this->requestStack->getCurrentRequest()->query->remove('destination');
    $authorization_endpoint = Url::fromUri($endpoints['authorization'], $url_options)->toString();

      $variables = array(
        '@message' => 'authorize',
        '@data' => json_encode($authorization_endpoint),
      );
      $this->loggerFactory->get('openid_connect_' . $this->pluginId)
        ->debug('@message. Details: @data', $variables);

    $response = new TrustedRedirectResponse($authorization_endpoint);
      $variables = array(
        '@message' => 'authorize response',
        '@data' => json_encode($response),
      );
      $this->loggerFactory->get('openid_connect_' . $this->pluginId)
        ->debug('@message. Details: @data', $variables);

    return $response;
  }

  /**
   * Overrides OpenIDConnectClientBase::retrieveUserInfo().
   */
  public function retrieveUserInfo($access_token) {
    $userinfo = parent::retrieveUserInfo($access_token);
    if ($userinfo) {
      // Azure AD returns email address in the upn field
      $userinfo['email'] = $userinfo['upn'];
    }

    return $userinfo;
  }
}
