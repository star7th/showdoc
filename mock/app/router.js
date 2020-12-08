'use strict';

/**
 * @param {Egg.Application} app - egg application
 */
module.exports = app => {
  const { router, controller } = app;
  router.all('/', controller.home.index);
  router.all('/mock', controller.home.mock);
};
