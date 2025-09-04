/*
 * This file is part of the TYPO3 CMS extension "myra_cloud_connector".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import DocumentService from '@typo3/core/document-service.js';
import Notification from '@typo3/backend/notification.js';
import RegularEvent from '@typo3/core/event/regular-event.js';

/**
 * Module: @cpsit/myra-cloud-connector/clear-cache-actions.js
 */
class ClearCacheActions {
  constructor() {
    DocumentService.ready().then(() => {
      const buttons = document.querySelectorAll('.t3js-clear-myra-cache');

      buttons.forEach((button) => {
        new RegularEvent('click', (event) => {
          ClearCacheActions.clearExternalCache(event.currentTarget.dataset.type, event.currentTarget.dataset.id, event.currentTarget.dataset.language);
        }).bindTo(button);
      });
    });
  }

  clearPageViaContextMenu(table, id) {
    let type = 0; // UNKNOWN

    if (table === 'pages') {
      type = 1; // Page
    } else if (table === 'sys_file' || table === 'sys_file_storage') {
      type = 2 // FileAdmin
    }

    ClearCacheActions.clearExternalCache(type, id, -1);
  };

  static clearExternalCache(type, pageId, languageId) {
    if (type > 0) {
      let errMsg = 'An error occurred while clearing the cache. It is likely not all caches were cleared as expected.';
      let errTitle = 'An error occurred';

      try {
        new AjaxRequest(TYPO3.settings.ajaxUrls.external_cache_clear)
          .withQueryArguments({id: pageId, type: type, language: languageId})
          .get()
          .then(
            async function (response) {
              let res = await response.resolve();

              try {
                if (res.hasOwnProperty('status') && res.status) {
                  Notification.success('Cache Clear', 'Successful', 2);
                } else if (res.hasOwnProperty('status') && !res.status) {
                  Notification.error(errTitle, res.message);
                } else {
                  Notification.error(errTitle, errMsg);
                }
              } catch (err) {
                Notification.error(errTitle, errMsg);
              }
            }
          ).catch(
          () => {
            Notification.error(errTitle, errMsg);
          }
        );
      } catch (e) {
        Notification.error(errTitle, errMsg);
      }
    }
  };
}

export default new ClearCacheActions();
