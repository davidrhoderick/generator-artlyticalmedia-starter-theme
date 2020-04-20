var Generator = require('yeoman-generator'),
    chalk = require('chalk');

module.exports = class extends Generator {
	constructor(args, opts) {
		super(args, opts);
	}

	async prompting() {
    this.answers = await this.prompt([{
      type   : 'input',
      name   : 'themename',
      message: 'Your theme name',
      default: 'Artlytical Media Starter Theme'
    }, {
      type   : 'input',
      name   : 'version',
      message: 'Your theme\'s version',
      default: '1.0.0'
    }, {
      type   : 'input',
      name   : 'repository',
      message: 'Your theme\'s repository',
      default: 'https://github.com/davidrhoderick/artlyticalmedia-starter-theme.git'
    }, {
      type   : 'input',
      name   : 'author',
      message: 'Your name',
      store  : true
    }, {
      type   : 'input',
      name   : 'email',
      message: 'Your email',
      store  : true
    }, {
      type   : 'input',
      name   : 'license',
      message: 'Your theme\'s license',
      default: 'MIT'
    }, {
      type   : 'confirm',
      name   : 'private',
      message: 'Is this project private?',
      default: true
    }, {
      type   : 'confirm',
      name   : 'bootstrap',
      message: 'Install Bootstrap dependencies?',
      choices: true
      }, {
      type   : 'input',
      name   : 'acfprokey',
      message: 'Your ACF Pro license key'
    }, {
      type   : 'input',
      name   : 'proxy',
      message: 'Your site\'s proxy server'
    }]);

    this.answers.themesafe = this.answers.themename.replace(/\s+/g, '-').toLowerCase();
    this.answers.authorsafe = this.answers.author.replace(/\s+/g, '').toLowerCase();
    this.answers.functionsafe = this.answers.themename.replace(/\s|[0-9]/g, '');

    if(this.answers.bootstrap) {
      this.answers.installedDependencies = 'Bootstrap 4 dependencies installed.';
      this.answers.styleSCSS = 'style-bootstrap.scss';
      this.answers.siteJS = 'site-bootstrap.js';
      this.answers.functionsPHP = 'functions-bootstrap.php';
      this.answers.composerJSON = 'composer-bootstrap.json';
    } else {
      this.answers.installedDependencies = 'no dependencies installed.';
      this.answers.styleSCSS = 'style-empty.scss';
      this.answers.siteJS = 'site-empty.js';
      this.answers.functionsPHP = 'functions-empty.php';
      this.answers.composerJSON = 'composer-empty.json';
    }
  }

  writing() {
    this.log(chalk.bold.green('\nCreating ' + ((this.answers.private) ? 'private' : 'public') + ' theme ' + this.answers.themename + 
      '(' + this.answers.repository + ')' +
      ' version ' + this.answers.version +
      ' by ' + this.answers.author + '(' + this.answers.email + ')' +
      ' with the ' + this.answers.license + ' license' +
      ' in folder wp-content/themes/' + this.answers.themesafe +
      ' with ' + this.answers.installedDependencies + '\n'));
  }

  install() {
    var themeDirectory = this.answers.themesafe;
    this.spawnCommandSync('git', ['clone', '-b', 'master', 'https://github.com/davidrhoderick/artlyticalmedia-starter-theme.git', themeDirectory]);
    
    this.fs.copyTpl(
      this.templatePath(this.answers.composerJSON),
      this.destinationPath(themeDirectory + '/composer.json'),
      {
        name      : this.answers.themesafe,
        authorsafe: this.answers.authorsafe,
        author    : this.answers.author,
        email     : this.answers.email,
        acfprokey : this.answers.acfprokey
      }
    );

    this.fs.copyTpl(
      this.templatePath('package.json'),
      this.destinationPath(themeDirectory + '/package.json'),
      {
        name      : this.answers.themesafe,
        version   : this.answers.version,
        repository: this.answers.repository,
        author    : this.answers.author,
        email     : this.answers.email,
        license   : this.answers.license,
        private   : this.answers.private
      }
    );

    this.fs.copyTpl(
      this.templatePath(this.answers.functionsPHP),
      this.destinationPath(themeDirectory + '/functions.php'),
      {
        themename   : this.answers.themename,
        version     : this.answers.version,
        repository  : this.answers.repository,
        functionsafe: this.answers.functionsafe
      }
    );

    this.fs.copyTpl(
      this.templatePath('gulpfile.js'),
      this.destinationPath(themeDirectory + '/gulpfile.js'),
      { proxy: this.answers.proxy });

    var workingDirectory = process.cwd() + '/' + themeDirectory;
    process.chdir(workingDirectory);
    this.installDependencies();

    this.fs.copyTpl(
      this.templatePath(this.answers.styleSCSS),
      this.destinationPath(themeDirectory + '/static/scss/style.scss'),
      {
        name      : this.answers.themename,
        version   : this.answers.version,
        author    : this.answers.author,
        license   : this.answers.license
      });

    if(this.answers.bootstrap) {
      this.fs.copyTpl(
        this.templatePath('variables-body.scss'),
        this.destinationPath(themeDirectory + '/static/scss/variables/_body.scss'));
      
      this.fs.copyTpl(
        this.templatePath('variables-fonts.scss'),
        this.destinationPath(themeDirectory + '/static/scss/variables/_fonts.scss'));

      this.fs.copyTpl(
        this.templatePath('variables-spacer.scss'),
        this.destinationPath(themeDirectory + '/static/scss/variables/_spacer.scss'));

      this.fs.copyTpl(
        this.templatePath('variables-colors.scss'),
        this.destinationPath(themeDirectory + '/static/scss/variables/_colors.scss'));

      this.fs.copyTpl(
        this.templatePath('variables-bootstrap.scss'),
        this.destinationPath(themeDirectory + '/static/scss/variables/_bootstrap.scss'));
      
      this.fs.copyTpl(
        this.templatePath('group_5e9db60dbef04.json'),
        this.destinationPath(themeDirectory + '/acf-json/group_5e9db60dbef04.json'));
    }

    this.fs.copyTpl(
      this.templatePath(this.answers.siteJS),
      this.destinationPath(themeDirectory + '/static/js/site.js'));
  }

  end() {
    this.spawnCommandSync('composer', ['install', '--ignore-platform-reqs']);
    
    this.spawnCommandSync('gulp');
  }
};