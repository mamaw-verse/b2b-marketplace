const emailIsValid = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());

window.hsTurnstileLoad ??= () => (window.HumanCheck?.ensure ? window.HumanCheck.ensure() : Promise.resolve());

window.authTabs = (active, loginUrl, registerUrl) => ({
    active,
    switchTab(direction) {
        const destination = direction === 'left'
            ? (this.active === 'login' ? registerUrl : loginUrl)
            : (this.active === 'login' ? registerUrl : loginUrl);

        window.Livewire?.navigate(destination) ?? window.location.assign(destination);
    },
});

window.loginForm = (values, hasServerErrors = false) => ({
    ...values,
    errors: {},
    capsLock: false,
    loading: false,
    submitted: false,
    lastMethod: window.localStorage.getItem('auth.lastMethod'),
    init() {
        if (hasServerErrors) {
            this.$nextTick(() => this.$refs.errorSummary?.focus());
        }
    },
    validateOnBlur(field) {
        const value = field === 'email' ? this.email.trim() : this.password;

        if (!this.submitted && value === '') {
            this.errors[field] = '';
            return;
        }

        this.validate(field);
    },
    validate(field) {
        if (field === 'email') {
            this.errors.email = this.email.trim() === ''
                ? 'Enter your email address.'
                : (emailIsValid(this.email) ? '' : 'Enter a valid email address.');
        }

        if (field === 'password') {
            this.errors.password = this.password === '' ? 'Enter your password.' : '';
        }
    },
    submit(event) {
        this.submitted = true;
        this.validate('email');
        this.validate('password');

        if (Object.values(this.errors).some(Boolean)) {
            event.preventDefault();
            this.$nextTick(() => this.$refs.clientErrorSummary?.focus());
            return;
        }

        this.loading = true;
        window.localStorage.setItem('auth.lastMethod', 'password');

        const form = event.target;
        const freshInput = form.querySelector('input[name="cf-turnstile-response"]');

        if (freshInput && !freshInput.value && window.HumanCheck?.fresh) {
            event.preventDefault();
            window.HumanCheck.fresh().then((token) => {
                freshInput.value = token;
                form.submit();
            }).catch(() => {
                this.loading = false;
            });
        }
    },
    remember(method) {
        window.localStorage.setItem('auth.lastMethod', method);
    },
});

window.registrationForm = (values, hasServerErrors = false) => ({
    ...values,
    errors: {},
    capsLock: false,
    loading: false,
    submitted: false,
    init() {
        if (hasServerErrors) {
            this.$nextTick(() => this.$refs.errorSummary?.focus());
        }
    },
    validateOnBlur(field) {
        const values = {
            firstName: this.firstName.trim(),
            lastName: this.lastName.trim(),
            email: this.email.trim(),
            password: this.password,
        };

        if (!this.submitted && values[field] === '') {
            this.errors[field] = '';
            return;
        }

        this.validate(field);
    },
    validate(field) {
        if (field === 'firstName') {
            this.errors.firstName = this.firstName.trim() === '' ? 'Enter your first name.' : '';
        }

        if (field === 'lastName') {
            this.errors.lastName = this.lastName.trim() === '' ? 'Enter your last name.' : '';
        }

        if (field === 'email') {
            this.errors.email = this.email.trim() === ''
                ? 'Enter your work email.'
                : (emailIsValid(this.email) ? '' : 'Enter a valid email address.');
        }

        if (field === 'password') {
            this.errors.password = this.password.length < 6 ? 'Use at least 6 characters.' : '';
        }

        if (field === 'terms') {
            this.errors.terms = this.terms ? '' : 'You must agree before creating an account.';
        }
    },
    get isValid() {
        return this.firstName.trim() !== ''
            && this.lastName.trim() !== ''
            && emailIsValid(this.email)
            && this.password.length >= 6
            && this.terms;
    },
    get strength() {
        if (this.password.length < 6) return 1;
        if (this.password.length < 10) return 2;
        if (this.password.length < 14) return 3;

        return 4;
    },
    get strengthLabel() {
        return ['', 'Weak', 'Fair', 'Good', 'Strong'][this.strength];
    },
    get strengthClass() {
        return [
            '',
            'bg-[var(--hs-danger-500)]',
            'bg-[var(--hs-warning-500)]',
            'bg-[var(--hs-info-500)]',
            'bg-[var(--hs-success-500)]',
        ][this.strength];
    },
    submit(event) {
        this.submitted = true;
        ['firstName', 'lastName', 'email', 'password', 'terms'].forEach((field) => this.validate(field));

        if (!this.isValid || Object.values(this.errors).some(Boolean)) {
            event.preventDefault();
            this.$nextTick(() => this.$refs.clientErrorSummary?.focus());
            return;
        }

        this.loading = true;
        window.localStorage.setItem('auth.lastMethod', 'password');

        const form = event.target;
        const freshInput = form.querySelector('input[name="cf-turnstile-response"]');

        if (freshInput && !freshInput.value && window.HumanCheck?.fresh) {
            event.preventDefault();
            window.HumanCheck.fresh().then((token) => {
                freshInput.value = token;
                form.submit();
            }).catch(() => {
                this.loading = false;
            });
        }
    },
});
