# Settings
## How to fill settings fields
For the project to work successfully, you must fill in the fields on the page domain/settings: 
 

*   
   Settings example:
    
    | Group    | Key       | Value                                         |
    |----------|-----------|-----------------------------------------------|
    | telegram | bot_token | 703592142:AAGedZspWYQ9Ba7h29JOjWr_NfjtFCumy5Y | 
    | telegram | chat_id   | -371816849                                    |
    | rocket_chat | rocket_chat_url | https://pop888.pw |
    | rocket_chat | rocket_chat_user | o.semenchuk | 
    | rocket_chat | rocket_chat_password | ******** |
    | notification | testing_merchant_id | 5 |
    | notification | public_key | qlZTm0U6YvF0QeEZ | 
    | notification | private_key | pgweRDPuTssaDtwBI5EotpfZHw3hdYaY |
    | notification | pps_url | http://master.backend.paygate.xim.hattiko.pw/ |  
    | notification | pps_api_url | http://master.api.paygate.xim.hattiko.pw/merchant |
    
    To check certain payment systems you mast create new testing merchant on PPS than add on setting page of this monitoring next field for created 
    merchant:
    testing_merchant_id,
    public_key,
    private_key
    
*
    Description:
    
    When adding each of the fields, you must specify the field group
    
        * Telegram fields settings:
                * bot_token - your bot token 
                * chat_id - chat id
        * Rocket chat fields settings:
                * rocket_chat_url - your rocket chat url 
                * rocket_chat_user - your rocket chat id
        * Project for testing data fields:
                * testing_merchant_id - testing merchant id                
                * public_key - testing merchant puplic key                
                * private_key - testing merchant private key  
        * Pps data fields:
                * pps_url current pps url for testing activity              
                * pps_api_url current pps api url for payment systems activity check             
  
    
    
    