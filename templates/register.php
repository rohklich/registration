<?php
\OCP\Util::addStyle('registration', 'style');
if ( \OCP\Util::getVersion()[0] >= 12 )
	\OCP\Util::addStyle('core', 'guest');
if ($_['entered']): ?>
	<?php if (empty($_['errormsg'])): ?>
		<ul class="msg">
			<li>
			<?php p($l->t('Thank you for registering, you should receive a verification link in a few minutes.')); ?>
			</li>
		</ul>
	<?php else: ?>
		<form action="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('registration.register.validateEmail')) ?>" method="post">
			<fieldset>
				<ul class="error">
					<li><?php p($_['errormsg']); ?></li>
				</ul>
				<?php if($_['secret_required']): ?>
					<p class="grouptop">
				<?php else: ?>
					<p class="groupofone">
				<?php endif; ?>
					<input type="email" name="email" id="email" placeholder="<?php p($l->t('Email')); ?>" value="<?php p($_['entered']);?>" required />
					<label for="email" class="infield"><?php p($l->t( 'Email' )); ?></label>
					<img id="email-icon" class="svg" src="<?php print_unescaped(image_path('', 'actions/mail.svg')); ?>" alt=""/>
				</p>
				<?php if($_['secret_required']): ?>
					<p class="groupbottom">
						<input type="text" name="reg_secret" id="reg-secret" placeholder="<?php p($l->t('Secret')); ?>" value="" autofocus/>
						<label for="reg_secret" class="infield"><?php p($l->t('Secret')); ?></label>
					</p>
				<?php endif; ?>
            	<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>" />
				<input type="submit" id="submit" value="<?php p($l->t('Request verification link')); ?>" />
			</fieldset>
		</form>
	<?php endif; ?>
<?php else: ?>
	<form action="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('registration.register.validateEmail')) ?>" method="post">
		<fieldset>
			<?php if ($_['errormsg']): ?>
				<ul class="error">
					<li><?php p($_['errormsg']); ?></li>
					<li><?php p($l->t('Please re-enter a valid email address')); ?></li>
				</ul>
			<?php else: ?>
				<ul class="msg">
					<li><?php p($l->t('You will receive an email with a verification link')); ?></li>
				</ul>
			<?php endif; ?>
				<?php if($_['secret_required']): ?>
					<p class="grouptop">
				<?php else: ?>
					<p class="groupofone">
				<?php endif; ?>
				<input type="email" name="email" id="email" placeholder="<?php p($l->t('Email')); ?>" value="" required autofocus />
				<label for="email" class="infield"><?php p($l->t('Email')); ?></label>
				<img id="email-icon" class="svg" src="<?php print_unescaped(image_path('', 'actions/mail.svg')); ?>" alt=""/>
			</p>
			<?php if($_['secret_required']): ?>
				<p class="groupbottom">
					<input type="text" name="reg_secret" id="reg-secret" placeholder="<?php p($l->t('Secret')); ?>" value=""/>
					<label for="reg_secret" class="infield"><?php p($l->t('Secret')); ?></label>
				</p>
			<?php endif; ?>
			<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>" />
			<input type="submit" id="submit" value="<?php p($l->t('Request verification link')); ?>" />
		</fieldset>
	</form>
<?php endif; ?>
