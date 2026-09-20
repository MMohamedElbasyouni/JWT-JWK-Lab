# JWT JWK Injection Lab

This lab is JWT based for handling sessions.

The server supports the `jwk` parameter in the JWT header, which is used by the developer to embed the correct verification key directly. Apart from that, it can be injected and used as trusted, since the server trusts the `jwk` parameter from any source, thinking that it can only be used by the backend itself.

## How to Run

Run the lab via Docker.

Start the lab locally using:

```
docker compose up --build
```

The lab port is `8080`.

## My Steps in Solving the Lab

We will be using the JWT Editor extension, so if you haven't downloaded it yet, you should download it from the Extensions tab.

This is our lab:

![lab home](images/1.png)

In this lab we are given a regular user account. Log into the account using the credentials:

```
user: mohamed
pass: mohamed@123
```

Here we see we are redirected to the profile page that has the message "Welcome mohamed":

![welcome mohamed](images/2.png)

So in this lab our target goal is to exploit the JWK token to have the privilege of the admin, which will give us here in the profile page "Welcome administrator".

I am using Burp as a proxy in my browser, so all my requests are being passed in my proxy history:

![proxy history](images/3.png)

Our login request is highlighted by the JWT extension, which means that it contains a JSON web token.

Now look at the POST request to the login endpoint. We give the application our username and password, and if they are correct, the application sets a session cookie that contains the JSON web token that will be passed in all the other requests to authenticate and authorize the user:

![login request](images/4.png)

So we are going to click on my profile page, and let's send it to the Repeater to work from the JWT extension:

![send to repeater](images/5.png)

As you can see in the `sub` claim, we are logged in with a regular user, so we need to try to find a way to alter this token to administrator.

There are only two stages in the JWT process we can think to make our attack on: the stage of signing the key, and the stage of verifying the key by the server.

In this lab, as we can see, the key is encoded with the asymmetric algorithm RS256, so we can't make our attack in the signing stage, since the key won't be verified with the public key if the private key has been manipulated.

That's why we have got no choice but to try to attack the verifying stage. But this verifying stage is made only by the server, unlike the signing stage, which we can manipulate.

The solution to solve this problem is to try to manipulate the JWT to make the server, who is the only one who can verify, verify with what I want, not what the server wants.

We can do that in this lab by injecting the `jwk` parameter, which makes the server verify directly with the key I gave it.

In this attack, at first we need to generate a public/private key pair, so we need to go to JWT Editor and click on "New RSA Key":

![new rsa key](images/6.png)

Here the key size doesn't matter, so we are just going to click on Generate:

![generate key](images/7.png)

It generated the key for us. Click OK.

Go back to the Repeater, and then we are going to go to the subject, changing that to `administrator`, and then we are going to click on the Attack button:

![change sub to administrator](images/8.png)

Click on "Embedded JWK":

![embedded jwk](images/9.png)

And then select the signing key we have just generated, then click OK:

![select signing key](images/10.png)

Notice over here it added our `jwk` parameter, and we have got the public key over here, and it used the equivalent private key in order to sign this token. So when the application receives this token, it will use the public key to check if the signature is valid. It will see that the signature is valid, and we should be able to access the app with admin privilege.

Click on Send:

![send request](images/11.png)

As we see, it worked successfully.
