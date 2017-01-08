<?php

namespace Drupal\social_auth_facebook\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures Simple FB Connect settings.
 */
class FacebookAuthSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Routing\RequestContext
   *
   * The request context.
   */
  protected $requestContext;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   Holds information about the current request.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestContext $request_context) {
    $this->requestContext = $request_context;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this class.
    return new static(
    // Load the services required to construct this class.
      $container->get('config.factory'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_facebook_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_auth_facebook.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_facebook.settings');

    $form['fb_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Facebook App settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Facebook App at <a href="@facebook-dev">@facebook-dev</a>', array('@facebook-dev' => 'https://developers.facebook.com/apps')),
    );

    $form['fb_settings']['app_id'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application ID'),
      '#default_value' => $config->get('app_id'),
      '#description' => $this->t('Copy the App ID of your Facebook App here. This value can be found from your App Dashboard.'),
    );

    $form['fb_settings']['app_secret'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('App Secret'),
      '#default_value' => $config->get('app_secret'),
      '#description' => $this->t('Copy the App Secret of your Facebook App here. This value can be found from your App Dashboard.'),
    );

    $form['fb_settings']['graph_version'] = array(
      '#type' => 'number',
      '#min' => 0,
      '#step' => 'any',
      '#required' => TRUE,
      '#title' => $this->t('Facebook Graph API version'),
      '#default_value' => $config->get('graph_version'),
      '#description' => $this->t('Copy the API Version of your Facebook App here. This value can be found from your App Dashboard. More information on API versions can be found at <a href="@facebook-changelog">Facebook Platform Changelog</a>', array('@facebook-changelog' => 'https://developers.facebook.com/docs/apps/changelog')),
    );

    $form['fb_settings']['oauth_redirect_url'] = array(
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Valid OAuth redirect URIs'),
      '#description' => $this->t('Copy this value to <em>Valid OAuth redirect URIs</em> field of your Facebook App settings.'),
      '#default_value' => $GLOBALS['base_url'] . '/user/login/facebook/callback',
    );

    $form['fb_settings']['app_domains'] = array(
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('App Domains'),
      '#description' => $this->t('Copy this value to <em>App Domains</em> field of your Facebook App settings.'),
      '#default_value' => $this->requestContext->getHost(),
    );

    $form['fb_settings']['site_url'] = array(
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Site URL'),
      '#description' => $this->t('Copy this value to <em>Site URL</em> field of your Facebook App settings.'),
      '#default_value' => $GLOBALS['base_url'],
    );

    $form['module_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Simple FB Connect configurations'),
      '#open' => TRUE,
      '#description' => $this->t('These settings allow you to configure how Simple FB Connect module behaves on your Drupal site'),
    );

    $form['module_settings']['post_login_path'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Post login path'),
      '#description' => $this->t('Drupal path where the user should be redirected after successful login. Use <em>&lt;front&gt;</em> to redirect user to your front page.'),
      '#default_value' => $config->get('post_login_path'),
    );

    $form['module_settings']['redirect_user_form'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect new users to Drupal user form'),
      '#description' => $this->t('If you check this, new users are redirected to Drupal user form after the user is created. This is useful if you want to encourage users to fill in additional user fields.'),
      '#default_value' => $config->get('redirect_user_form'),
    );

    $form['module_settings']['disable_admin_login'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Disable FB login for administrator'),
      '#description' => $this->t('Disabling FB login for administrator (<em>user 1</em>) can help protect your site if a security vulnerability is ever discovered in Facebook PHP SDK or this module.'),
      '#default_value' => $config->get('disable_admin_login'),
    );

    // Option to disable FB login for specific roles.
    $roles = user_roles();
    $options = array();
    foreach ($roles as $key => $role_object) {
      if ($key != 'anonymous' && $key != 'authenticated') {
        $options[$key] = SafeMarkup::checkPlain($role_object->get('label'));
      }
    }

    $form['module_settings']['disabled_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Disable FB login for the following roles'),
      '#options' => $options,
      '#default_value' => $config->get('disabled_roles'),
    );
    if (empty($roles)) {
      $form['module_settings']['disabled_roles']['#description'] = $this->t('No roles found.');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^[2-9]\.[0-9]{1,2}$/', $form_state->getValue('graph_version'))) {
      $form_state->setErrorByName('graph_version', $this->t('Invalid API version. The syntax for API version is for example <em>v2.8</em>'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_facebook.settings')
      ->set('app_id', $values['app_id'])
      ->set('app_secret', $values['app_secret'])
      ->set('graph_version', $values['graph_version'])
      ->set('post_login_path', $values['post_login_path'])
      ->set('redirect_user_form', $values['redirect_user_form'])
      ->set('disable_admin_login', $values['disable_admin_login'])
      ->set('disabled_roles', $values['disabled_roles'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
