<h1 align="center">Introduction to the Warcmachine</h1>
<p align="center">The Warcmachine is here to help you process the WARC files provided by Common Crawl</p>

Warcmachine was built to simplify and cheapify the process of 'grepping the internet'.

With Warcmachine, you can:

Build and test regex patterns against real Common Crawl data
Easily load Common Crawl datasets for parallel processing
Scale compute capabilities to asynchronously crunch through WARCs at frankly unreasonable capacity.
Store and easily retrieve the results

## This Project would not be possible without:
- @c6fc and his tool [WARCannon](https://github.com/c6fc/warcannon)
- @philippelyp and his tool [php-warc](https://github.com/philippelyp/php-warc)


## How it works
Warcmachine leverages the Cloud Servers of [Hetzner](https://hetzner.cloud/?ref=OrRwLRrar54y) (this is my referral link) to horizontally scale to any capacity, parallelize across hundreds of CPU cores, and store results where you want the to be stored (for example SFTP or S3).

I linked my referral link for Hetzner which will give you 20€ of credit for free - and should you decide that you like Hetzner and their service then I will get 10€ in the case that you spend more than 10€ with them. Any use of the referral link is highly appreciated.

## Performance/Cost differences between Warcmachine and WARCannon
In the example pictures on the bottom of the WARCannon readme we can see that using a 172 Core Server the WARCannon goes through 112 WARCs in 340 seconds. This means that on average approximately every 3 seconds one WARC is being processed by the 172 Core Server.
-> This is with the regex's by @c6fc

Using the WARCannon I needed roughly 1 minute for 1 WARC on a 1vCPU test server with my regex -> 973.52 in 340 seconds 

This instance with 172 Cores (c5n.18xlarge) costs 3.89$ per hour.
The servers we will be using (Hetzner CX21) cost 0.0095€ per hour.

During my measurements the Hetzner CX21 was able to process 2 WARCs per minute (roughly) (1 per vCore), but we will use 90 Seconds in our calculations, just in case.

Ok now some simple Math:

CX21:

2 WARCs per 90 seconds 
7.54 WARCs per 340 seconds

So if you spend the same money:

3.89 / 0.0095 = 409
7.54 * 409 = 3083 WARCs in 340 seconds

So if my calculation are correct and we use pessimistic times for the processing of the WARCs on Hetzner Cloud Servers the performance still should be much better.

But what about the Network speed and the time it takes to download the WARCs?
-> can almost be neglected, using aria2c you can download the WARCs in ~ 5 seconds on the Hetzner Server compared to 1-2 seconds on the AWS Servers

## What you will need:
- A [Hetzner](https://hetzner.cloud/?ref=OrRwLRrar54y) (this is my referral link) account
- 1 Redis Server
- A place to Store all the Data


## Installation
0. Fork this project

1. Clone this project
    ```bash
    git clone https://github.com/____YOUR USERNAME____/warcmachine
    cd warcmachine
    ```
2. Install dependencies
    ```bash
    composer install
    ```

3. Set up Laravel configurations
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. Set your database & redis in .env

5. Migrate database
    ```bash
    php artisan migrate
    ```

6. Fille the Redis Server with WARCs
    ```bash
    php artisan insertWARCS CC-MAIN-2022-27 400
    ```

    Syntax: php artisan insertWARCS __WARC_NAME__ __LIMIT__

7. Use the cloudinit.mysql.cfg or cloudinit.sqlite.cfg and edit it
    ```bash
    cp cloudinit.sqlite.cfg .cloudinit.cfg
    ```
    All lines which you need to edit have comments on them

8. Edit the ProcessWarcs Job and add your own regex
9. Edit the UploadResults Job and add your own storage
10. Go to Hetzner and start 10 CX21 Servers at a time with the following config: EDIT!: USE UBUNTU 22.04 - I DID NOT TEST UBUNTU 20.04

![Image](https://raw.githubusercontent.com/userlip/warcmachine/main/Hetzner.png)

11. Do not disable the IPv4 (I dont know why but it didnt work with only IPv6 in my tests)
12. Insert your Cloudinit and create the servers
13. The Cloud Server will now Process all the WARCs from the Queue
14. You can edit the ProcessWarcs Job so it uploads your results immediatly or do it like me and use another Job for it - in this case you need to start that Job now too so you can upload the results.
15. Check on the size of the queue reguarly, the Servers do not delete themselves - if someone wants to make use of the Hetzner API and manage the server part even better, a pull request is highly appreciated.

## Contributing
Feel free to contribute and make a pull request.
