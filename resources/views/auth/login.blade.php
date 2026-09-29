```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>DMW HRIS - Login</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    >

    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.3s ease-out;
        }

        .privacy-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .privacy-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .privacy-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
    </style>
</head>

<body class="relative min-h-screen flex items-center justify-center font-sans overflow-hidden">

    {{-- Background --}}
    <div class="fixed inset-0 z-0">
        <img
            src="{{ asset('images/background-DMWHRIS.jpg') }}"
            class="w-full h-full object-cover"
            alt="Background"
        >
    </div>

    {{-- Main page content --}}
    <div
        class="relative z-10 container mx-auto flex flex-col lg:flex-row
               items-center justify-center gap-12 px-6 py-8"
    >

        {{-- Department title --}}
        <div class="text-white max-w-lg text-center lg:text-left">
            <h1 class="text-5xl font-bold leading-tight">
                Department of <br>
                Migrant Workers
            </h1>

            <p class="mt-4 text-xl italic font-light opacity-90">
                Kagawaran ng Manggagawang Pandarayuhan
            </p>
        </div>

        {{-- Login card --}}
        <div class="bg-white rounded-2xl shadow-2xl p-10 w-full max-w-md">

            {{-- Logo --}}
            <div class="flex flex-col items-center mb-8">
                <img
                    src="{{ asset('images/logo-DMW.png') }}"
                    alt="HRIS Logo"
                    class="h-24 w-auto object-contain"
                >
            </div>

            {{-- Login form --}}
            <form
                method="POST"
                action="{{ route('login') }}"
                id="loginForm"
                novalidate
            >
                @csrf
                <input
                    type="hidden"
                    name="privacy_consent"
                    id="privacyConsentInput"
                    value=""
                >

                {{-- HRIS Username --}}
                <div class="mb-4">
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        placeholder="example@dmw.gov.ph"
                        pattern="^[^@\s]+@dmw\.gov\.ph$"
                        title="Invalid email."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg
                               focus:outline-none focus:ring-2 focus:ring-blue-800
                               transition duration-200
                               @error('email') border-red-500 @enderror"
                        required
                        autofocus
                    >

                    <span
                        id="emailError"
                        class="hidden block text-red-500 text-xs mt-1"
                    >
                        Invalid email.
                    </span>

                    @error('email')
                        <span class="block text-red-500 text-xs mt-1">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-6">
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            placeholder="password"
                            class="w-full px-4 py-3 pr-11 border border-gray-300
                                   rounded-lg focus:outline-none focus:ring-2
                                   focus:ring-blue-800 transition duration-200
                                   @error('password') border-red-500 @enderror"
                            required
                        >

                        <button
                            type="button"
                            id="togglePasswordButton"
                            class="absolute right-3 top-1/2 -translate-y-1/2
                                   text-gray-400 hover:text-gray-600"
                            aria-label="Show or hide password"
                        >
                            <i
                                id="eyeIcon"
                                class="fas fa-eye-slash"
                            ></i>
                        </button>
                    </div>

                    <span
                        id="passwordError"
                        class="hidden block text-red-500 text-xs mt-1"
                    >
                        Password is required.
                    </span>

                    @error('password')
                        <span class="block text-red-500 text-xs mt-1">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="mb-4 flex items-center">
                    <input
                        id="remember_me"
                        name="remember"
                        type="checkbox"
                        class="h-4 w-4 text-blue-800 focus:ring-blue-800
                               border-gray-300 rounded"
                    >

                    <label
                        for="remember_me"
                        class="ml-2 block text-sm text-gray-900"
                    >
                        Remember me
                    </label>
                </div>

                {{-- Sign in button --}}
                <button
                    type="submit"
                    id="signInButton"
                    class="w-full bg-[#1e3a8a] text-white font-bold py-3
                           rounded-lg hover:bg-blue-900 transition duration-300
                           shadow-lg disabled:opacity-60
                           disabled:cursor-not-allowed"
                >
                    <span id="signInButtonText">
                        SIGN IN
                    </span>

                    <span
                        id="signInLoadingText"
                        class="hidden"
                    >
                        
                        SIGN IN
                    </span>
                </button>

                {{-- Account information --}}
                <div class="mt-8 text-center space-y-2">
                    <p class="text-gray-700 text-sm">
                        {{ __('Accounts are created by Human Resource personnel.') }}
                    </p>

                    @if (Route::has('password.request'))
                        <a
                            href="{{ route('password.request') }}"
                            class="text-blue-500 text-sm hover:underline"
                        >
                            Forgot password?
                        </a>
                    @endif
                </div>

                {{-- Government logos --}}
                <div class="mt-8 flex justify-center items-center gap-4">
                    <img
                        src="https://www.gppb.gov.ph/wp-content/uploads/2023/07/Logo-Bagong-Pilipinas.png"
                        alt="Bagong Pilipinas"
                        class="h-10"
                    >

                    <img
                        src="https://dmw.gov.ph/images/dmw_logo.png"
                        alt="DMW Seal"
                        class="h-10"
                    >
                </div>
            </form>
        </div>
    </div>

    {{-- Data Privacy Act modal --}}
    <div
        id="privacyModal"
        class="hidden fixed inset-0 z-50 items-center justify-center px-4 py-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="privacyModalTitle"
    >
        {{-- Overlay --}}
        <div
            id="privacyModalOverlay"
            class="absolute inset-0 bg-black/60 backdrop-blur-sm"
        ></div>

        {{-- Modal container --}}
        <div
            class="relative w-full max-w-xl max-h-[90vh] overflow-hidden
                   bg-white rounded-2xl shadow-2xl animate-fade-in-up"
        >

            {{-- Header --}}
            <div
                class="flex items-start justify-between gap-4 px-5 py-4
                       border-b border-gray-200"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center
                               rounded-full bg-blue-100"
                    >
                        <i class="fas fa-shield-alt text-sm text-blue-800"></i>
                    </div>

                    <div>
                        <h2
                            id="privacyModalTitle"
                            class="text-sm font-bold text-gray-800"
                        >
                            Data Privacy Act of 2012
                        </h2>

                        <p class="text-[10px] text-gray-500">
                            Republic Act No. 10173
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    id="closePrivacyModalButton"
                    class="flex h-8 w-8 shrink-0 items-center justify-center
                           rounded-full text-gray-400 hover:bg-gray-100
                           hover:text-gray-600 transition"
                    aria-label="Close privacy notice"
                >
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            {{-- Privacy text --}}
            <div
                class="privacy-scrollbar max-h-[48vh] overflow-y-auto
                       px-5 py-4"
            >
                <div class="space-y-3 text-[11px] leading-relaxed text-gray-600">

                    <p>
                        Pursuant to the Data Privacy Act of 2012, I acknowledge
                        that the Department of Migrant Workers may collect,
                        store, use, process, and disclose my personal data for
                        authorized Human Resource Information System purposes.
                    </p>

                    <p>
                        The information processed through this system may
                        include my name, contact details, employment
                        information, attendance records, leave records,
                        credentials, account information, and other records
                        necessary for legitimate human resource operations.
                    </p>

                    <p>
                        I understand that my personal information shall be
                        processed only for legitimate and authorized purposes
                        and shall be protected through reasonable
                        organizational, physical, and technical security
                        measures.
                    </p>

                    <p>
                        I acknowledge my rights as a data subject, including
                        the right to be informed, access my information,
                        object to its processing, correct inaccurate
                        information, and request the suspension or withdrawal
                        of my personal data, subject to applicable laws and
                        regulations.
                    </p>

                    <p>
                        By checking the agreement box below, I confirm that I
                        have read and understood this privacy notice and
                        acknowledge the processing of my personal information
                        in accordance with Republic Act No. 10173 and its
                        implementing rules and regulations.
                    </p>

                    <div
                        class="rounded-lg border border-blue-100
                               bg-blue-50 p-3"
                    >
                        <p class="text-[10px] leading-relaxed text-blue-800">
                            <i class="fas fa-info-circle mr-1"></i>

                            Read the full text of the

                            <a
                                href="https://privacy.gov.ph/data-privacy-act/"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-semibold underline"
                            >
                                Data Privacy Act of 2012
                            </a>.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Agreement --}}
            <div class="px-5 py-4 border-t border-gray-200 bg-gray-50">
                <label
                    for="privacyAgreementCheckbox"
                    class="flex items-start gap-2 cursor-pointer"
                >
                    <input
                        type="checkbox"
                        id="privacyAgreementCheckbox"
                        name="privacy_consent"
                        value="1"

                        class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300
                               text-blue-800 focus:ring-blue-800 cursor-pointer"
                    >

                    <span class="text-[11px] leading-relaxed text-gray-700">
                        I agree to the collection and processing of my
                        personal information in accordance with the Data
                        Privacy Act of 2012.
                    </span>
                </label>

                <p
                    id="privacyAgreementError"
                    class="hidden mt-2 text-[10px] text-red-500"
                >
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Please check “I agree” before continuing.
                </p>
            </div>

            {{-- Buttons --}}
            <div
                class="flex flex-col-reverse sm:flex-row sm:justify-end
                       gap-2 px-5 py-4 border-t border-gray-200"
            >
                <button
                    type="button"
                    id="cancelPrivacyButton"
                    class="w-full sm:w-auto px-4 py-2 text-xs font-semibold
                           text-gray-600 border border-gray-300 rounded-lg
                           hover:bg-gray-100 transition duration-200"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    id="continuePrivacyButton"
                    disabled
                    class="w-full sm:w-auto px-5 py-2 text-xs font-bold
                           text-white bg-[#1e3a8a] rounded-lg
                           hover:bg-blue-900 transition duration-200
                           disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Continue
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const loginForm = document.getElementById('loginForm');
            const privacyConsentInput =
                document.getElementById('privacyConsentInput');

            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('emailError');

            const passwordInput = document.getElementById('password');
            const passwordError = document.getElementById('passwordError');

            const togglePasswordButton =
                document.getElementById('togglePasswordButton');

            const eyeIcon = document.getElementById('eyeIcon');

            const signInButton = document.getElementById('signInButton');
            const signInButtonText =
                document.getElementById('signInButtonText');

            const signInLoadingText =
                document.getElementById('signInLoadingText');

            const privacyModal = document.getElementById('privacyModal');

            const privacyModalOverlay =
                document.getElementById('privacyModalOverlay');

            const closePrivacyModalButton =
                document.getElementById('closePrivacyModalButton');

            const cancelPrivacyButton =
                document.getElementById('cancelPrivacyButton');

            const privacyAgreementCheckbox =
                document.getElementById('privacyAgreementCheckbox');

            const privacyAgreementError =
                document.getElementById('privacyAgreementError');

            const continuePrivacyButton =
                document.getElementById('continuePrivacyButton');

            const officialEmailPattern =
                /^[^@\s]+@dmw\.gov\.ph$/;

            const privacyConsentStatusUrl =
                @json(route('login.privacy-consent-status'));

            const csrfToken =
                document.querySelector('input[name="_token"]').value;

            let loginSubmissionRequested = false;

            function validateEmailField(showWhenEmpty = false) {
                const value = emailInput.value.trim();

                const isValid =
                    value !== '' &&
                    officialEmailPattern.test(value);

                const shouldShowError =
                    value === ''
                        ? showWhenEmpty
                        : !isValid;

                emailInput.classList.toggle(
                    'border-red-500',
                    shouldShowError
                );

                emailInput.classList.toggle(
                    'focus:ring-red-500',
                    shouldShowError
                );

                emailInput.classList.toggle(
                    'focus:ring-blue-800',
                    !shouldShowError
                );

                emailError.classList.toggle(
                    'hidden',
                    !shouldShowError
                );

                return isValid;
            }

            function validatePasswordField() {
                const isValid =
                    passwordInput.value.trim() !== '';

                passwordInput.classList.toggle(
                    'border-red-500',
                    !isValid
                );

                passwordInput.classList.toggle(
                    'focus:ring-red-500',
                    !isValid
                );

                passwordInput.classList.toggle(
                    'focus:ring-blue-800',
                    isValid
                );

                passwordError.classList.toggle(
                    'hidden',
                    isValid
                );

                return isValid;
            }

            function openPrivacyModal() {
                loginSubmissionRequested = true;

                privacyAgreementCheckbox.checked = false;
                continuePrivacyButton.disabled = true;
                privacyAgreementError.classList.add('hidden');

                privacyModal.classList.remove('hidden');
                privacyModal.classList.add('flex');

                document.body.classList.add('overflow-hidden');

                setTimeout(function () {
                    privacyAgreementCheckbox.focus();
                }, 100);
            }

            function closePrivacyModal() {
                loginSubmissionRequested = false;

                privacyModal.classList.add('hidden');
                privacyModal.classList.remove('flex');

                document.body.classList.remove('overflow-hidden');

                privacyAgreementCheckbox.checked = false;
                continuePrivacyButton.disabled = true;
                privacyAgreementError.classList.add('hidden');
            }

            function showLoadingState() {
                signInButton.disabled = true;

                signInButtonText.classList.add('hidden');
                signInLoadingText.classList.remove('hidden');
            }

            function submitLoginForm() {
                showLoadingState();

                HTMLFormElement.prototype.submit.call(loginForm);
            }

            async function shouldRequestPrivacyConsent() {
                try {
                    const response = await fetch(privacyConsentStatusUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            email: emailInput.value.trim().toLowerCase(),
                        }),
                    });

                    if (!response.ok) {
                        return false;
                    }

                    const data = await response.json();

                    return Boolean(data.requires_consent);
                } catch (error) {
                    return false;
                }
            }

            emailInput.addEventListener('input', function () {
                validateEmailField(false);
            });

            emailInput.addEventListener('blur', function () {
                validateEmailField(false);
            });

            passwordInput.addEventListener('input', function () {
                if (passwordInput.value.trim() !== '') {
                    validatePasswordField();
                }
            });

            togglePasswordButton.addEventListener('click', function () {
                const isPasswordHidden =
                    passwordInput.type === 'password';

                passwordInput.type =
                    isPasswordHidden ? 'text' : 'password';

                eyeIcon.classList.toggle(
                    'fa-eye',
                    isPasswordHidden
                );

                eyeIcon.classList.toggle(
                    'fa-eye-slash',
                    !isPasswordHidden
                );
            });

            loginForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                const emailIsValid =
                    validateEmailField(true);

                const passwordIsValid =
                    validatePasswordField();

                if (!emailIsValid) {
                    emailInput.focus();
                    return;
                }

                if (!passwordIsValid) {
                    passwordInput.focus();
                    return;
                }

                if (privacyConsentInput.value === '1') {
                    submitLoginForm();
                    return;
                }

                const requiresPrivacyConsent =
                    await shouldRequestPrivacyConsent();

                if (requiresPrivacyConsent) {
                    openPrivacyModal();
                    return;
                }

                submitLoginForm();
            });

            /*
             * This is the important fix:
             * checking the box directly changes the disabled property
             * of the Continue button.
             */
            privacyAgreementCheckbox.addEventListener(
                'change',
                function () {
                    continuePrivacyButton.disabled =
                        !privacyAgreementCheckbox.checked;

                    if (privacyAgreementCheckbox.checked) {
                        privacyAgreementError.classList.add('hidden');
                    }
                }
            );

            continuePrivacyButton.addEventListener(
                'click',
                function () {
                    if (!privacyAgreementCheckbox.checked) {
                        privacyAgreementError.classList.remove('hidden');
                        privacyAgreementCheckbox.focus();
                        return;
                    }

                    if (!loginSubmissionRequested) {
                        closePrivacyModal();
                        return;
                    }

                    privacyModal.classList.add('hidden');
                    privacyModal.classList.remove('flex');

                    document.body.classList.remove('overflow-hidden');

                    privacyConsentInput.value = '1';
                    submitLoginForm();
                }
            );

            closePrivacyModalButton.addEventListener(
                'click',
                closePrivacyModal
            );

            cancelPrivacyButton.addEventListener(
                'click',
                closePrivacyModal
            );

            privacyModalOverlay.addEventListener(
                'click',
                closePrivacyModal
            );

            document.addEventListener('keydown', function (event) {
                const modalIsOpen =
                    !privacyModal.classList.contains('hidden');

                if (event.key === 'Escape' && modalIsOpen) {
                    closePrivacyModal();
                }
            });
        });
    </script>

</body>
</html>
```
