<form class="form-register" action="/index.php?r=user/register" method="post">
    <h2 class="form-signin-heading">Please register</h2>
    <label for="inputEmail" class="sr-only">Email address</label>
    <input type="email" name="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus
        <?= isset($_POST['email']) ? 'value='.$_POST['email']:'' ?>>
    <label for="inputPassword" class="sr-only">Password</label>
    <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>
    <label for="inputPassword" class="sr-only">Password</label>
    <input type="password" name="repeat_password" id="repeat_password" class="form-control" placeholder="Retype password" required>
    <button class="btn btn-lg btn-primary btn-block" type="submit">Register</button>
</form>